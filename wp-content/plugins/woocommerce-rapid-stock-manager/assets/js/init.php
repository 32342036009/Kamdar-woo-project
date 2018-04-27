<?php
header('Content-Type: text/javascript');

$version = array_key_exists('version', $_GET) ? $_GET['version'] : '';
$environment = array_key_exists('environment', $_GET) ? $_GET['environment'] : '';
echo 'var ishoutyRsmVersion = "' . $version . '";' . "\n";
echo 'var ishoutyRsmEnvironment = "' . $environment . '";' . "\n";
?>



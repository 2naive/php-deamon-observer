<?php

include 'Observer.class.php';

DEFINE('LOGS_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs');

ini_set('error_log', LOGS_DIR.'/error.log');

fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);

$STDIN  = fopen('/dev/null', 'r');
$STDOUT = fopen(LOGS_DIR.'/application.log', 'ab');
$STDERR = fopen(LOGS_DIR.'/error.log', 'ab');

$observer = new Observer( ! empty($argv[1]) ? $argv[1] : NULL);
$observer->run();

?>
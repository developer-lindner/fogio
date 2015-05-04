<?php
define("PATH_TO_CREDENTIALS", "./app/Fogio/config/");

if ($argc < 1) {
    print "\nCredentials needed. Stopping!\n";
    exit();
}

$credentials_called = $argv[1];
$credentials_config_file = $argv[1] . 'php';

if (!is_file(PATH_TO_CREDENTIALS . $credentials_config_file)) {
    print "\nNo such credentials found. Stopping!\n";
    exit;
}

use Fogio\Fogio;

require_once './vendor/autoload.php';
require_once PATH_TO_CREDENTIALS . $credentials_config_file;

$fogio = new Fogio($credentials);

$fogio->setFogBugzFilter('30');
$fogio->setFogBugzQuery('opened:"last week..today"');
$fogio->setPlanioQuery();

$fogio->importToPlanio();

print "\n-- FIN --\n";
exit;

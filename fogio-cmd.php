<?php

use Fogio\Fogio;

require_once './vendor/autoload.php';
require_once './app/Fogio/config/credentials.php';

$fogio = new Fogio($credentials);

$fogio->setFogBugzFilter('30');
$fogio->setFogBugzQuery('opened:"last week..today"');
$fogio->setPlanioQuery();

$fogio->importToPlanio();

print "\n-- FIN --\n";
exit;

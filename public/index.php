<?php

use Fogio\Fogio;

require_once '../vendor/autoload.php';
require_once '../app/Fogio/config/credentials.php';

$fogio = new Fogio($credentials);

$fogio->importToPlanio();

print "\n-- FIN --\n";
exit;

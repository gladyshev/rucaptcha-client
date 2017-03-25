<?php

require '../vendor/autoload.php';

$client = new Rucaptcha\Client(getenv('__RUCAPTCHA_KEY__'));

$pingbackUrl = 'http://' . getenv('__HOST__') .'/captcha/pingback.php';

// Add pingback url to allowed list
$client->addPingback($pingbackUrl);

// List of allowed pingbacks
$allowedPingbacks = $client->getPingbacks();

var_dump($allowedPingbacks);

$client->deletePingback($pingbackUrl);

// Check
var_dump($client->getPingbacks());

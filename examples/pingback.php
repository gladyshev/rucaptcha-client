<?php

require '../vendor/autoload.php';

$client = new Rucaptcha\Client(
    Rucaptcha\Config::fromApiKey(getenv('__RUCAPTCHA_KEY__')),
    new GuzzleHttp\Client()
);

$pingbackUrl = 'http://' . getenv('__HOST__') .'/captcha/pingback.php';

// Add pingback url to allowed list
$client->addPingback($pingbackUrl);

// List of allowed pingbacks
$allowedPingbacks = $client->getPingbacks();

var_dump($allowedPingbacks);

$client->deletePingback($pingbackUrl);

// Check
var_dump($client->getPingbacks());

<?php

require '../vendor/autoload.php';

$client = new Rucaptcha\Client(
    Rucaptcha\Config::fromApiKey(getenv('__RUCAPTCHA_KEY__')),
    new GuzzleHttp\Client()
);

$balance = $client->getBalance();

var_dump($balance);

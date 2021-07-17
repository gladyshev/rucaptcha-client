<?php

require '../vendor/autoload.php';

$rucaptcha = new Rucaptcha\Client(
    Rucaptcha\Config::fromApiKey(getenv('__RUCAPTCHA_KEY__')),
    new GuzzleHttp\Client()
);

var_dump($rucaptcha->recognizeFile(__DIR__ . '/data/captcha.png'));

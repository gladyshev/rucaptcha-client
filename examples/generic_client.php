<?php

require '../vendor/autoload.php';

$anticaptchaConfig = new Rucaptcha\Config(
    getenv('__ANTICAPTCHA_KEY__'),
    'http://anti-captcha.com'
);

$client = new Rucaptcha\GenericClient(
    $anticaptchaConfig,
    new GuzzleHttp\Client()
);

// Recognize using Anticaptcha service
$captchaText = $client->recognizeFile(__DIR__ . '/data/captcha.png');

var_dump($captchaText);

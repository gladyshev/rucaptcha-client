<?php

require '../vendor/autoload.php';

$captchaText = (new Rucaptcha\Client(getenv('__RUCAPTCHA_KEY__')))->recognizeFile(__DIR__ . '/data/captcha.png');

var_dump($captchaText);
<?php

require '../vendor/autoload.php';

use Rucaptcha\GenericClient;

/**
 * Anti-captcha.com API client
 * Class Anticaptcha
 */
class Anticaptcha extends GenericClient
{
    protected $serverBaseUri = 'http://anti-captcha.com';
}

$captchaText = (new Anticaptcha(getenv('__ANTICAPTCHA_KEY__')))->recognizeFile(__DIR__ . '/data/captcha.png');

var_dump($captchaText);

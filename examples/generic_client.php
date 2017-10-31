<?php

require '../vendor/autoload.php';

use Rucaptcha\GenericClient;

/*
 * Changes the server base URL is enough to start using anti-captcha.com API
 *
 * But you may to use my specific library https://github.com/gladyshev/anticaptcha-client
 */
class Anticaptcha extends GenericClient
{
    protected $serverBaseUri = 'http://anti-captcha.com';
}

$captchaText = (new Anticaptcha(getenv('__ANTICAPTCHA_KEY__')))->recognizeFile(__DIR__ . '/data/captcha.png');

var_dump($captchaText);

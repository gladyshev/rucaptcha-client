<?php

require '../vendor/autoload.php';

$balance = (new Rucaptcha\Client(getenv('__RUCAPTCHA_KEY__')))->getBalance();

var_dump($balance);

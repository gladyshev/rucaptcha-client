<?php

require '../vendor/autoload.php';

$loadData = (new Rucaptcha\Client(getenv('__RUCAPTCHA_KEY__')))->getLoad('waiting');

var_dump($loadData);

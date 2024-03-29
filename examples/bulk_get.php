<?php

require '../vendor/autoload.php';

$rucaptcha = new Rucaptcha\Client(
    Rucaptcha\Config::fromApiKey(getenv('__RUCAPTCHA_KEY__')),
    new GuzzleHttp\Client()
);

$taskIds = [];

$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents(__DIR__.'/data/captcha.png'));
$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents(__DIR__.'/data/yandex.gif'), [
    Rucaptcha\Extra::IS_RUSSIAN => 1
]);

$results = [];

while (true) {
    // Wait 5 sec
    sleep(5);

    $results = $rucaptcha->getCaptchaResultBulk($taskIds);

    if (!in_array(false, $results, true)) {
        break;
    }
}

print_r($results);

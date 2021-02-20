<?php

require '../vendor/autoload.php';

$rucaptcha = new Rucaptcha\Client(getenv('__RUCAPTCHA_KEY__'), [
    'verbose' => true,
    'httpClient' => new GuzzleHttp\Client(['base_uri' => 'https://2captcha.com']),
    'logger'     => new Monolog\Logger('2Captcha', [new Monolog\Handler\StreamHandler('php://stdout')])
]);

$taskIds = [];

$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents(__DIR__.'/data/captcha.png'));
//$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents(__DIR__.'/data/unreadable.png'));
$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents(__DIR__.'/data/yandex.gif'), [
    Rucaptcha\Extra::IS_RUSSIAN => 1
]);

$results = [];

while (count($taskIds) > 0) {
    // Try get results
    foreach ($taskIds as $i => $taskId) {
        // Wait 5 sec
        sleep(5);

        $results[$taskId] = $rucaptcha->getCaptchaResult($taskId);

        // false === is not ready or exception on error
        if ($results[$taskId] === false) {
            continue;
        } else {
            unset($taskIds[$i]);
        }
    }
}

print_r($results);

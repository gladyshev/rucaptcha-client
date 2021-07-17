<?php

require '../vendor/autoload.php';

$rucaptcha = new Rucaptcha\Client(
    Rucaptcha\Config::fromApiKey(getenv('__RUCAPTCHA_KEY__')),
    new GuzzleHttp\Client(),
    new \Monolog\Logger('Rucaptcha', [new \Monolog\Handler\StreamHandler('php://stdout')])
);

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

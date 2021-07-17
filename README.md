rucaptcha-client
================
Удобная PHP-обёртка для сервиса распознавания капчи [rucaptcha.com](https://rucaptcha.com?from=1342124).  
Оригинальная документация доступна [по ссылке](https://rucaptcha.com/api-rucaptcha?from=1342124).

[![Build Status](https://travis-ci.org/gladyshev/rucaptcha-client.svg?branch=v2)](https://travis-ci.org/gladyshev/rucaptcha-client)
[![Code Coverage](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/badges/coverage.png?b=v2)](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/badges/quality-score.png?b=v2)](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/?branch=master)

### Install 

```bash
$ composer require --prefer-dist gladyshev/rucaptcha-client "*"
```
or 
```php
"require": {
  ...
  "gladyshev/rucaptcha-client": "^2.0.0"
  ...
}
```

### Examples
Больше примеров в папке [examples](/examples).

```php
/* Simple */

$rucaptcha = new Rucaptcha\Client('YOUR_API_KEY');

$captchaText = $rucaptcha->recognizeFile('captcha.png');
print_r($captchaText); // h54g6
```

```php
/* Advanced example */

$rucaptcha = new \Rucaptcha\Client(
    \Rucaptcha\Config::fromApiKey('YOUR_API_KEY'),
    new \GuzzleHttp\Client(['base_uri' => 'https://2captcha.com']),
    new \Monolog\Logger('2Captcha', [new \Monolog\Handler\StreamHandler('php://stdout')])
);

$taskIds = [];

$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents('captcha1.png'));
$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents('captcha2.jpg'));
$taskIds[] = $rucaptcha->sendCaptcha(file_get_contents('captcha3.gif'), [
    Rucaptcha\Extra::NUMERIC => 1
]);

$results = [];

while (count($taskIds) > 0) 
{
    // Try get results
    foreach ($taskIds as $i=>$taskId) 
    {    
        // Wait 5 sec
        sleep(5);
        
        $results[$taskId] = $rucaptcha->getCaptchaResult($taskId);
        
        // false === is not ready, on error we've got an exception
        if ($results[$taskId] === false) {
            continue;
        } else {
            unset($taskIds[$i]);
        }
    }
}

print_r($results);
```

### Methods of `Rucaptcha\Client`

```php
use Rucaptcha\Client;

/* Solving captcha methods */

Client::recognize(string $content, array $extra = []): string;
Client::recognizeFile(string $path, array $extra = []): string;
Client::sendCaptcha(string $content, array $extra = []): int;
Client::getCaptchaResult(int $captchaId): string;
Client::getCaptchaResultBulk(array $captchaIds): array;

/* Pingback stuff */

Client::addPingback(string $uri): void;
Client::getPingbacks(): array;
Client::deletePingback(string $uri): void;
Client::deleteAllPingbacks(): void;

/* Google Recaptcha V2 */

Client::sendRecapthaV2($googleKey, $pageUrl, $extra = []): int
Client::recognizeRecapthaV2($googleKey, $pageUrl, $extra = []): string

/* Other */

Client::getLastCaptchaId(): string;
Client::getBalance(): string;
Client::reportGood(string $captchaId): bool;
Client::reportBad(string $captchaId): bool;
```

### Client options

Параметр | Тип | По умолчанию | Возможные значения
---| --- | --- | ---
`verbose` | bool | false | Включает/отключает логирование в стандартный вывод
`apiKey`| string | '' | Ключ API с которым вызывается сервис
`rTimeout`| integer	| 5 | Период между опросами серевера при получении результата распознавания
`mTimeout` | integer | 120 | Таймаут ожидания ответа при получении результата распознавания
`serverBaseUri`| string | 'http://rucaptcha.com' | Базовый URI сервиса


### Solving options `$extra`

Параметр | Тип | По умолчанию | Возможные значения
---| --- | --- | ---
`phrase` | integer  | 0 | 0 = одно слово <br> 1 = капча имеет два слова
`regsense`| integer	| 0 | 0 = регистр ответа не имеет значения <br>  1 = регистр ответа имеет значение
`question`| integer	 | 0 | 0 = параметр не задействован <br>  1 = на изображении задан вопрос, работник должен написать ответ
`numeric` | integer | 0 | 0 = параметр не задействован <br>  1 = капча состоит только из цифр<br>  2 = Капча состоит только из букв<br>  3 = Капча состоит либо только из цифр, либо только из букв.
`calc`| integer | 0 | 0 = параметр не задействован <br>  1 = работнику нужно совершить математическое действие с капчи
`min_len` | 0..20 | 0 | 0 = параметр не задействован <br>  1..20 = минимальное количество знаков в ответе
`max_len` | 1..20 | 0 | 0 = параметр не задействован<br>  1..20 = максимальное количество знаков в ответе
`is_russian` | integer | 0 | параметр больше не используется, т.к. он означал "слать данную капчу русским исполнителям", а в системе находятся только русскоязычные исполнители. Смотрите новый параметр language, однозначно обозначающий язык капчи
`soft_id` | string | | ID разработчика приложения. Разработчику приложения отчисляется 10% от всех капч, пришедших из его приложения.
`language` | integer | 0 | 0 = параметр не задействован <br> 1 = на капче только кириллические буквы <br> 2 = на капче только латинские буквы
`lang` | string |  | Код языка. [См. список поддерживаемых языков](https://rucaptcha.com/api-rucaptcha#language).
`header_acao` | integer	| 0 | 0 = значение по умолчанию <br> 1 = in.php передаст Access-Control-Allow-Origin: * параметр в заголовке ответа. (Необходимо для кросс-доменных AJAX запросов в браузерных приложениях. Работает также для res.php.)
`textinstructions` | string |  |Текст, который будет показан работнику. Может содержать в себе инструкции по разгадке капчи. Ограничение - 140 символов. Текст необходимо слать в кодировке UTF-8.
`textcaptcha` | string | | Текстовая капча. Картинка при этом не загружается, работник получает только текст и вводит ответ на этот текст. Ограничение - 140 символов. Текст необходимо слать в кодировке UTF-8.
`pingback` | string | | URL для автоматической отправки ответа на капчу (callback). URL должен быть зарегистрирован на сервере. [Больше информации здесь](https://rucaptcha.com/api-rucaptcha#pingback).
`recaptcha` | string | | Используется при работе со старым алгоритмом распознования Google Recaptcha V2. [Больше информации здесь](https://rucaptcha.com/api-rucaptcha#solving_recaptchav2_old). 
`proxy` | string | | Формат: логин:пароль@123.123.123.123:3128 [Больше информации о прокси здесь.](https://rucaptcha.com/api-rucaptcha#proxies)
`proxytype` | string | | Тип вашего прокси-сервера: HTTP, HTTPS, SOCKS4, SOCKS5.

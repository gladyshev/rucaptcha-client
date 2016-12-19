rucaptcha-client
================
PHP-клиент сервиса распознавания капчи [rucaptcha.com](https://rucaptcha.com?from=1342124).

[![Build Status](https://travis-ci.org/gladyshev/rucaptcha-client.svg?branch=master)](https://travis-ci.org/gladyshev/rucaptcha-client)
[![Code Coverage](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gladyshev/rucaptcha-client/?branch=master)

### Примеры ###

```php
/* Simple */

$rucaptcha = new Rucaptcha\Client('YOUR_API_KEY');

$captchaText = $rucaptcha->recognizeFile('captcha.png');
print_r($captchaText);
```
```php
/* Advanced example */

$rucaptcha = new Rucaptcha\Client('YOUR_API_KEY', [
    'verbose' => true
]);

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

### Установка ###

```php
"require": {
  ...
  "gladyshev/rucaptcha-client": "~1.0"
  ...
}
```

### Методы `Rucaptcha\Client` ###

```php
use Rucaptcha\Client;

/* Constructor */

Client::__construct($apiKey, array $options = []) : void;


/* Configuration */

Client::setOptions(array $options) : void;

// Guzzle PSR-7 HTTP-client
Client::setHttpClient(GuzzleHttp\ClientInterface $client) : void;

// PSR-3 logger
Client::setLogger(Psr\Log\LoggerInterface $logger) : void;


/* Solving captcha methods */

Client::recognize(string $content, array $extra = []) : string;
Client::recognizeFile(string $path, array $extra = []) : string;
Client::sendCaptcha(string $content, array $extra = []) : string;
Client::getCaptchaResult(string $captchaId) : string;


/* Other */

Client::getLastCaptchaId() : string;
Client::getBalance() : string;
Client::badCaptcha(string $captchaId) : bool;
Client::getLoad(array $paramsList = []) : array;
```


### Опции клиента ###

Параметр | Тип | По умолчанию | Возможные значения
---| --- | --- | ---
`verbose` | bool | false | Включает/отключает логирование в стандартный вывод
`apiKey`| string | '' | Ключ API с которым вызывается сервис
`rTimeout`| integer	| 5 | Период между опросами серевера при получении результата распознавания
`mTimeout` | integer | 120 | Таймаут ожидания ответа при получении результата распознавания
`serverBaseUri`| string | 'http://rucaptcha.com' | Базовый URI сервиса


### Параметры распознавания капчи `$extra` ###

Параметр | Тип | По умолчанию | Возможные значения
---| --- | --- | ---
`phrase` | integer  | 0 | 0 = одно слово <br/> 1 = капча имеет два слова
`regsense`| integer	| 0 | 0 = регистр ответа не имеет значения <br>  1 = регистр ответа имеет значение
`question`| integer	 | 0 | 0 = параметр не задействован <br>  1 = на изображении задан вопрос, работник должен написать ответ
`numeric` | integer | 0 | 0 = параметр не задействован <br>  1 = капча состоит только из цифр<br>  2 = Капча состоит только из букв<br>  3 = Капча состоит либо только из цифр, либо только из букв.
`calc`| integer | 0 | 0 = параметр не задействован <br>  1 = работнику нужно совершить математическое действие с капчи
`min_len` | 0..20 | 0 | 0 = параметр не задействован <br>  1..20 = минимальное количество знаков в ответе
`max_len` | 1..20 | 0 | 0 = параметр не задействован<br>  1..20 = максимальное количество знаков в ответе
`is_russian` | integer | 0 | параметр больше не используется, т.к. он означал "слать данную капчу русским исполнителям", а в системе находятся только русскоязычные исполнители. Смотрите новый параметр language, однозначно обозначающий язык капчи
`soft_id` | string | | ID разработчика приложения. Разработчику приложения отчисляется 10% от всех капч, пришедших из его приложения.
`language` | integer | 0 | 0 = параметр не задействован <br> 1 = на капче только кириллические буквы <br>2 = на капче только латинские буквы
`header_acao` | integer	| 0 | 0 = значение по умолчанию <br> 1 = in.php передаст Access-Control-Allow-Origin: * параметр в заголовке ответа. (Необходимо для кросс-доменных AJAX запросов в браузерных приложениях. Работает также для res.php.)
`textinstructions` | string |  |Текст, который будет показан работнику. Может содержать в себе инструкции по разгадке капчи. Ограничение - 140 символов. Текст необходимо слать в кодировке UTF-8.
`textcaptcha` | string | | Текстовая капча. Картинка при этом не загружается, работник получает только текст и вводит ответ на этот текст. Ограничение - 140 символов. Текст необходимо слать в кодировке UTF-8.

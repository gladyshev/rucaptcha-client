<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;


class Error
{
    const KEY_DOES_NOT_EXIST            = 'ERROR_KEY_DOES_NOT_EXIST';
    const WRONG_ID_FORMAT               = 'ERROR_WRONG_ID_FORMAT';
    const WRONG_USER_KEY                = 'ERROR_WRONG_USER_KEY';
    const ZERO_BALANCE                  = 'ERROR_ZERO_BALANCE';
    const CAPTCHA_UNSOLVABLE            = 'ERROR_CAPTCHA_UNSOLVABLE';
    const NO_SLOT_AVAILABLE             = 'ERROR_NO_SLOT_AVAILABLE';
    const WRONG_CAPTCHA_ID              = 'ERROR_WRONG_CAPTCHA_ID';
    const ZERO_CAPTCHA_FILESIZE         = 'ERROR_ZERO_CAPTCHA_FILESIZE';
    const BAD_DUPLICATES                = 'ERROR_BAD_DUPLICATES';
    const TOO_BIG_CAPTCHA_FILESIZE      = 'ERROR_TOO_BIG_CAPTCHA_FILESIZE';
    const WRONG_FILE_EXTENSION          = 'ERROR_WRONG_FILE_EXTENSION';
    const IMAGE_TYPE_NOT_SUPPORTED      = 'ERROR_IMAGE_TYPE_NOT_SUPPORTED';
    const IP_NOT_ALLOWED                = 'ERROR_IP_NOT_ALLOWED';
    const IP_BANNED                     = 'ERROR_IP_BANNED';

    static public $messages = [
        self::KEY_DOES_NOT_EXIST       => 'Использован несуществующий key.',
        self::WRONG_ID_FORMAT          => 'Неверный формат ID капчи. ID должен содержать только цифры.',
        self::WRONG_USER_KEY           => 'Не верный формат параметра key, должно быть 32 символа',
        self::ZERO_BALANCE             => 'Баланс Вашего аккаунта нулевой.',
        self::CAPTCHA_UNSOLVABLE       => 'Капчу не смогли разгадать 3 разных работника. Списанные средства за это изображение возвращаются обратно на баланс.',
        self::NO_SLOT_AVAILABLE        => 'Текущая ставка распознования выше, чем максимально установленная в настройках Вашего аккаунта. Либо на сервере скопилась очередь и работники не успевают её разобрать, повторите загрузку через 5 секунд.',
        self::WRONG_CAPTCHA_ID         => 'Вы пытаетесь получить ответ на капчу или пожаловаться на капчу, которая была загружена более 15 минут назад.',
        self::ZERO_CAPTCHA_FILESIZE    => 'Размер капчи меньше 100 Байт.',
        self::BAD_DUPLICATES           => 'Ошибка появляется при включённом 100%м распознании. Было использовано максимальное количество попыток, но необходимое количество одинаковых ответов не было набрано.',
        self::TOO_BIG_CAPTCHA_FILESIZE => 'Размер капчи более 100 КБайт.',
        self::WRONG_FILE_EXTENSION     => 'Ваша капча имеет неверное расширение, допустимые расширения jpg,jpeg,gif,png.',
        self::IMAGE_TYPE_NOT_SUPPORTED => 'Сервер не может определить тип файла капчи.',
        self::IP_NOT_ALLOWED           => 'В Вашем аккаунте настроено ограничения по IP с которых можно делать запросы. И IP, с которого пришёл данный запрос не входит в список разрешённых.',
        self::IP_BANNED                => 'IP-адрес, с которого пришёл запрос заблокирован из-за частых обращений с различными неверными ключами. Блокировка снимается через час.',
    ];
}
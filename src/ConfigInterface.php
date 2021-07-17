<?php
/**
 * @project rucaptcha-client
 */

namespace Rucaptcha;

interface ConfigInterface
{
    /**
     * Your application ID in Rucaptcha catalog.
     * The value `1013` is ID of this library. Set in false if you want to turn off sending any ID.
     *
     * @see https://rucaptcha.com/software/view/php-api-client
     * @var string
     */
    public function getSoftId(): string;

    public function getApiKey(): string;

    public function getMTimeout(): int;

    public function getRTimeout(): int;

    public function getRecaptchaRTimeout(): int;

    public function getServerBaseUri(): string;
}

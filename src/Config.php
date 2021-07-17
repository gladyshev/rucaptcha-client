<?php
/**
 * @project rucaptcha-client
 */

namespace Rucaptcha;


final class Config implements ConfigInterface
{
    private string $softId;
    private string $apiKey;
    private string $serverBaseUri;
    private int $recaptchaRTimeout;
    private int $rTimeout;
    private int $mTimeout;

    public function __construct(
        string $apiKey,
        string $serverBaseUri = 'http://rucaptcha.com',
        int $recaptchaRTimeout = 15,
        string $softId = '1013',
        int $rTimeout = 5,
        int $mTimeout = 120
    ) {
        $this->apiKey = $apiKey;
        $this->serverBaseUri = $serverBaseUri;
        $this->recaptchaRTimeout = $recaptchaRTimeout;
        $this->softId = $softId;
        $this->rTimeout = $rTimeout;
        $this->mTimeout = $mTimeout;
    }

    public static function fromApiKey(string $apiKey): self
    {
        return new self(
            $apiKey
        );
    }

    public function getMTimeout(): int
    {
        return $this->mTimeout;
    }

    public function getRTimeout(): int
    {
        return $this->rTimeout;
    }

    public function getRecaptchaRTimeout(): int
    {
        return $this->recaptchaRTimeout;
    }

    public function getServerBaseUri(): string
    {
        return $this->serverBaseUri;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getSoftId(): string
    {
        return $this->softId;
    }
}
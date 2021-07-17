<?php

namespace Rucaptcha;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rucaptcha\Exception\ErrorResponseException;
use Rucaptcha\Exception\InvalidArgumentException;
use Rucaptcha\Exception\RuntimeException;
use SplFileObject;
use Throwable;

class GenericClient
{
    /* Statuses */
    const STATUS_OK = 'OK';
    const STATUS_CAPTCHA_NOT_READY = 'CAPCHA_NOT_READY';

    protected string $lastCaptchaId = '';
    protected ConfigInterface $config;
    protected ClientInterface $httpClient;
    protected LoggerInterface $logger;

    /**
     * GenericClient constructor.
     *
     * @param ConfigInterface $config
     * @param ClientInterface $httpClient
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ConfigInterface $config,
        ClientInterface $httpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @return string   # Last successfully sent captcha task ID
     */
    public function getLastCaptchaId(): string
    {
        return $this->lastCaptchaId;
    }

    /**
     * @param string $path
     * @param array $extra
     *
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function recognizeFile(string $path, array $extra = []): string
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("Captcha file `$path` not found.");
        }

        $file = new SplFileObject($path, 'r');

        $content = '';

        while (!$file->eof()) {
            $content .= $file->fgets();
        }

        return $this->recognize($content, $extra);
    }

    /**
     * @param string $content
     * @param array $extra
     * 
     * @return string
     * 
     * @throws Throwable
     */
    public function recognize(string $content, array $extra = []): string
    {
        $captchaId = $this->sendCaptcha($content, $extra);
        $startTime = time();

        while (true) {
            $this->logger->info("Waiting {$this->config->getRTimeout()} sec.");

            sleep($this->config->getRTimeout());

            if (time() - $startTime >= $this->config->getMTimeout()) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $result = $this->getCaptchaResult($captchaId);

            if ($result === null) {
                continue;
            }

            $this->logger->info("Elapsed " . (time()-$startTime) . " second(s).");

            return $result;
        }
    }

    /**
     * @param string $content       # Captcha image content
     * @param array $extra          # Array of recognition options
     * @return string               # Captcha task ID
     * @throws Throwable
     */
    public function sendCaptcha(string $content, array $extra = []): string
    {
        $this->logger->info("Try send captcha image on {$this->config->getServerBaseUri()}/in.php");

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $body = http_build_query(array_merge($extra, [
            'method' => 'base64',
            'key' => $this->config->getApiKey(),
            'body' => base64_encode($content)
        ]));

        $request = new Request('POST', $this->config->getServerBaseUri() . '/in.php', $headers, $body);

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if (mb_strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->logger->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? "Unknown error: `{$responseText}`."
        );
    }

    /**
     * @param string $captchaId     # Captcha task ID
     * @return string|null          # Solved captcha text or false if captcha is not ready
     * @throws Throwable
     */
    public function getCaptchaResult(string $captchaId): ?string
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=get&id={$captchaId}"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_CAPTCHA_NOT_READY) {
            return null;
        }

        if (mb_strpos($responseText, 'OK|') !== false) {
            $this->logger->info("Got OK response: `{$responseText}`.");
            return html_entity_decode(trim(explode('|', $responseText)[1]));
        }

        throw new ErrorResponseException(Error::$messages[$responseText] ?? $responseText);
    }
}

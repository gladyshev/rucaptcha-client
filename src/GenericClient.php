<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;


use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Rucaptcha\Exception\InvalidArgumentException;
use Rucaptcha\Exception\RucaptchaException;
use Rucaptcha\Exception\RuntimeException;
use SplFileObject;

class GenericClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ConfigurableTrait;

    /* Statuses */
    const STATUS_OK = 'OK';
    const STATUS_CAPTCHA_NOT_READY = 'CAPCHA_NOT_READY';

    /**
     * @var string
     */
    protected $lastCaptchaId = '';

    /**
     * @var string
     */
    protected $serverBaseUri = '';

    /**
     * @var bool
     */
    protected $verbose = false;

    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var int
     */
    protected $rTimeout = 5;

    /**
     * @var int
     */
    protected $mTimeout = 120;

    /**
     * @var ClientInterface
     */
    private $httpClient = null;

    /**
     * @param array $options
     * @param string $apiKey
     */
    public function __construct($apiKey, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->setOptions($options);
    }

    /**
     * @return string   # Last successfully sent captcha task ID
     */
    public function getLastCaptchaId()
    {
        return $this->lastCaptchaId;
    }

    /**
     * @param ClientInterface $client
     * @return $this
     */
    public function setHttpClient(ClientInterface $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * @param string $path
     * @param array $extra
     * @return string
     * @throws RucaptchaException
     */
    public function recognizeFile($path, array $extra = [])
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
     * @return string
     * @throws RuntimeException
     */
    public function recognize($content, array $extra = [])
    {
        /* Send image to recognition server */

        $this->getLogger()->info("Try send captcha image on {$this->serverBaseUri}/in.php");

        $captchaId = $this->sendCaptcha($content, $extra);


        /* Get captcha recognition result */

        $this->getLogger()->info("Sending success. Got captcha id `$captchaId`.");

        $startTime = time();

        while (true) {

            $this->getLogger()->info("Waiting {$this->rTimeout} sec.");

            sleep($this->rTimeout);

            if (time() - $startTime >= $this->mTimeout) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $result = $this->getCaptchaResult($captchaId);

            if ($result === false) {
                continue;
            }

            $this->getLogger()->info("Got OK response: `{$result}`. Elapsed " . (time() - $startTime) . ' sec.');

            return $result;
        }

        throw new RuntimeException('Unknown recognition logic error.');
    }

    /**
     * @param string $content       # Captcha image content
     * @param array $extra          # Array of recognition options
     * @return string               # Captcha task ID
     * @throws RuntimeException
     */
    public function sendCaptcha($content, array $extra = [])
    {
        $response = $this->getHttpClient()->request('POST', '/in.php', [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            RequestOptions::FORM_PARAMS => array_merge($extra, [
                'method' => 'base64',
                'key' => $this->apiKey,
                'body' => base64_encode($content)
            ])
        ]);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            return $this->lastCaptchaId;
        }

        throw new RuntimeException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @param string $captchaId     # Captcha task ID
     * @return string|false         # Solved captcha text or false if captcha is not ready
     * @throws RuntimeException
     */
    public function getCaptchaResult($captchaId)
    {
        $response = $this->getHttpClient()->request('GET', "/res.php?key={$this->apiKey}&action=get&id={$captchaId}");

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_CAPTCHA_NOT_READY) {
            return false;
        }

        if (strpos($responseText, 'OK|') !== false) {
            return html_entity_decode(trim(explode('|', $responseText)[1]));
        }

        throw new RuntimeException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @param string $responseText  # Server response text usually begin with `ERROR_` prefix
     * @return false|string         # Error message text or false if associated message in not found
     */
    protected function getErrorMessage($responseText)
    {
        return isset(Error::$messages[$responseText])
            ? Error::$messages[$responseText]
            : false;
    }

    /**
     * @return ClientInterface
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = new GuzzleClient([
                'base_uri' => $this->serverBaseUri
            ]);
        }
        return $this->httpClient;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        if ($this->logger === null)
        {
            $defaultLogger = new Logger;
            $defaultLogger->verbose = & $this->verbose;

            $this->setLogger($defaultLogger);
        }
        return $this->logger;
    }
}
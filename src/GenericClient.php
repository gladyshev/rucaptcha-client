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
    public $lastCaptchaId = '';

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
    private $client = null;

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
     * @param $path
     * @param array $extra
     * @return string
     * @throws RucaptchaException
     */
    public function recognizeFile($path, array $extra = [])
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("Captcha file `$path` not found.");
        }

        $fp = fopen($path, 'r');

        $content = '';

        while (!feof($fp)) {
            $content .= fgets($fp, 1024);
        }

        fclose($fp);

        if (isset($extra[Extra::CONTENT_TYPE])) {
            $extension = self::resolveFileExtension($path);
            $extra[Extra::CONTENT_TYPE] = self::resolveContentType($extension);
        }

        return $this->recognize($content, $extra);
    }

    /**
     * @param ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        if ($this->client === null) {
            $this->client = new GuzzleClient(['base_uri' => $this->serverBaseUri]);
        }
        return $this->client;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        if ($this->logger === null) {
            $this->setLogger(new Logger($this->verbose));
        }
        return $this->logger;
    }

    /**
     * @param $content
     * @param array $extra
     * @return string
     * @throws RuntimeException
     */
    public function recognize($content, array $extra = [])
    {
        /* Send image to recognition server */

        $this->getLogger()->info("Try send captcha image on {$this->serverBaseUri}/in.php");

        $response = $this->getClient()->request('POST', '/in.php', [
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

        if (strpos($responseText, 'ERROR') !== false
            || strpos($responseText, '<HTML>') !== false
            || strpos($responseText, '|') === false
            || in_array($responseText, array_keys(Error::$messages))
        ) {
            throw new RuntimeException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
        }


        /* Get captcha recognition result */

        list($status, $captchaId) = explode("|", $responseText);

        $this->getLogger()->info("Sending success. Got captcha id `$captchaId`.");

        $startTime = time();

        $this->lastCaptchaId = $captchaId;

        while (true) {
            unset($response, $responseText, $status);

            $this->getLogger()->info("Waiting {$this->rTimeout} sec.");

            sleep($this->rTimeout);

            if (time() - $startTime >= $this->mTimeout) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $response = $this->getClient()->request('GET', "/res.php?key={$this->apiKey}&action=get&id={$captchaId}");

            $responseText = $response->getBody()->__toString();

            if ($responseText === self::STATUS_CAPTCHA_NOT_READY) {
                continue;
            }

            if (strpos($responseText, 'OK|') !== false) {

                $this->getLogger()->info("Got OK response: {$responseText}. Elapsed " . (time() - $startTime) . ' sec.');

                list($status, $captchaText) = explode('|', $responseText);

                return html_entity_decode(trim($captchaText));
            }
            throw new RuntimeException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
        }
    }

    /**
     * @param string $responseText
     * @return false|string
     */
    protected function getErrorMessage($responseText)
    {
        return isset(Error::$messages[$responseText])
            ? Error::$messages[$responseText]
            : false;
    }

    /**
     * @param string $extension
     * @return string
     * @throws RucaptchaException
     */
    protected static function resolveContentType($extension)
    {
        // ToDo: refactor this bullshit

        if (empty($extension)) {
            throw new InvalidArgumentException("The type of content cannot be detected, because file extension is empty.");
        }

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                return "image/pjpeg";

            default:
                return 'image/' . $extension;
        }
    }

    /**
     * @param string $path
     * @param string $delimiter
     * @return string
     * @throws InvalidArgumentException
     */
    protected static function resolveFileExtension($path, $delimiter = '.')
    {
        //ToDo: use SPL helper

        if (($position = strrpos($path, $delimiter)) === false) {
            throw new InvalidArgumentException("Could not resolve file `{$path}` extension.");
        }

        return strtolower(substr($path, ++$position));
    }
}
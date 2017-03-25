<?php

namespace Rucaptcha;

use Rucaptcha\Exception\ErrorResponseException;
use Rucaptcha\Exception\RuntimeException;

/**
 * Class Client
 *
 * @package Rucaptcha
 * @author Dmitry Gladyshev <deel@email.ru>
 */
class Client extends GenericClient
{
    const STATUS_OK_REPORT_RECORDED = 'OK_REPORT_RECORDED';

    /**
     * @var string
     */
    protected $serverBaseUri = 'http://rucaptcha.com';

    /**
     * Your application ID in Rucaptcha catalog.
     * The value `1013` is ID of this library. Set in false if you want to turn off sending any ID.
     * @see https://rucaptcha.com/software/view/php-api-client
     * @var string
     */
    protected $softId = '1013';

    /**
     * @inheritdoc
     */
    public function sendCaptcha($content, array $extra = [])
    {
        if ($this->softId && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->softId;
        }
        return parent::sendCaptcha($content, $extra);
    }

    /**
     * Bulk captcha result.
     *
     * @param int[] $captchaIds     # Captcha task Ids array
     * @return string[]             # Array $captchaId => $captchaText or false if is not ready
     * @throws RuntimeException
     */
    public function getCaptchaResultBulk(array $captchaIds)
    {
        $response = $this->getHttpClient()->request('GET', '/res.php?' . http_build_query([
                'key' => $this->apiKey,
                'action' => 'get',
                'ids' => join(',', $captchaIds)
            ]));

        $captchaTexts = $response->getBody()->__toString();

        $this->getLogger()->info("Got bulk response: `{$captchaTexts}`.");

        $captchaTexts = explode("|", $captchaTexts);

        $result = [];

        foreach ($captchaTexts as $index => $captchaText) {
            $captchaText = html_entity_decode(trim($captchaText));
            $result[$captchaIds[$index]] =
                ($captchaText == self::STATUS_CAPTCHA_NOT_READY) ? false : $captchaText;
        }

        return $result;
    }

    /**
     * Returns balance of account.
     *
     * @return string
     */
    public function getBalance()
    {
        $response = $this->getHttpClient()->request('GET', "/res.php?key={$this->apiKey}&action=getbalance");
        return $response->getBody()->__toString();
    }

    /**
     * Report of wrong recognition.
     *
     * @param $captchaId
     * @return bool
     * @throws RuntimeException
     */
    public function badCaptcha($captchaId)
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=reportbad&id={$captchaId}");

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK_REPORT_RECORDED) {
            return true;
        }
        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * Returns server health data.
     *
     * @param string|string[] $paramsList   # List of metrics to be returned
     * @return array                        # Array of load metrics $metric => $value formatted
     */
    public function getLoad($paramsList = ['waiting', 'load', 'minbid', 'averageRecognitionTime'])
    {
        $parser = $this->getLoadXml();

        if (is_string($paramsList)) {
            return $parser->$paramsList->__toString();
        }

        $statusData = [];

        foreach ($paramsList as $item) {
            $statusData[$item] = $parser->$item->__toString();
        }

        return $statusData;
    }

    /**
     * Returns load data as XML.
     *
     * @return \SimpleXMLElement
     */
    public function getLoadXml()
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/load.php");

        return new \SimpleXMLElement($response->getBody()->__toString());
    }

    /**
     * @param string $captchaId     # Captcha task ID
     * @return array | false        # Solved captcha and cost array or false if captcha is not ready
     * @throws RuntimeException
     */
    public function getCaptchaResultWithCost($captchaId)
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=get2&id={$captchaId}");

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_CAPTCHA_NOT_READY) {
            return false;
        }

        if (strpos($responseText, 'OK|') !== false) {
            $this->getLogger()->info("Got OK response: `{$responseText}`.");
            $data = explode('|', $responseText);
            return [
                'captcha' => html_entity_decode(trim($data[1])),
                'cost' => html_entity_decode(trim($data[2])),
            ];
        }

        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @param string $url
     * @return bool                     # true if added and exception if fail
     * @throws RuntimeException
     */
    public function addPingback($url)
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=add_pingback&addr={$url}");

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK) {
            return true;
        }
        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @return string[]
     * @throws RuntimeException
     */
    public function getPingbacks()
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=get_pingback");

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $data = explode('|', $responseText);
            unset($data[0]);
            return empty($data[1]) ? [] : array_values($data);
        }

        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @param string $uri
     * @return bool
     * @throws RuntimeException
     */
    public function deletePingback($uri)
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=del_pingback&addr={$uri}");

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK) {
            return true;
        }
        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @return bool
     * @throws RuntimeException
     */
    public function deleteAllPingbacks()
    {
        return $this->deletePingback('all');
    }
}

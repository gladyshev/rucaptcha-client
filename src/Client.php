<?php

namespace Rucaptcha;

use GuzzleHttp\RequestOptions;
use Rucaptcha\Exception\ErrorResponseException;
use Rucaptcha\Exception\InvalidArgumentException;
use Rucaptcha\Exception\RuntimeException;

/**
 * Class Client
 *
 * @package Rucaptcha
 * @author Dmitry Gladyshev <deel@email.ru>
 */
class Client extends GenericClient
{
    /* json status codes */
    const STATUS_CODE_CAPCHA_NOT_READY = 0;
    const STATUS_CODE_OK = 1;

    /* status codes */
    const STATUS_OK_REPORT_RECORDED = 'OK_REPORT_RECORDED';

    /**
     * @var int
     */
    protected $recaptchaRTimeout = 15;

    /**
     * @var string
     */
    protected $serverBaseUri = 'http://rucaptcha.com';

    /**
     * Your application ID in Rucaptcha catalog.
     * The value `1013` is ID of this library. Set in false if you want to turn off sending any ID.
     *
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
     * @param int[] $captchaIds # Captcha task Ids array
     * @return string[]                 # Array $captchaId => $captchaText or false if is not ready
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBalance()
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=getbalance");

        return $response->getBody()->__toString();
    }

    /**
     * Report of wrong recognition.
     *
     * @param string $captchaId
     * @return bool
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        throw new ErrorResponseException(
            $this->getErrorMessage($responseText) ?: $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Returns server health data.
     *
     * @param string|string[] $paramsList   # List of metrics to be returned
     * @return string[]|string              # Array of load metrics $metric => $value formatted
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        throw new ErrorResponseException(
            $this->getErrorMessage($responseText) ?: $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Add pingback url to rucaptcha whitelist.
     *
     * @param string $url
     * @return bool                     # true if added and exception if fail
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        throw new ErrorResponseException(
            $this->getErrorMessage($responseText) ?: $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Returns pingback whitelist items.
     *
     * @return string[]                 # List of urls
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        throw new ErrorResponseException(
            $this->getErrorMessage($responseText) ?: $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Remove pingback url from whitelist.
     *
     * @param string $uri
     * @return bool
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        throw new ErrorResponseException(
            $this->getErrorMessage($responseText) ?: $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Truncate pingback whitelist.
     *
     * @return bool
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteAllPingbacks()
    {
        return $this->deletePingback('all');
    }

    /* Recaptcha v2 */

    /**
     * Sent recaptcha v2
     *
     * @param string $googleKey
     * @param string $pageUrl
     * @param array $extra
     * @return string
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRecaptchaV2($googleKey, $pageUrl, $extra = [])
    {
        $this->getLogger()->info("Try send google key (recaptcha)  on {$this->serverBaseUri}/in.php");

        if ($this->softId && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->softId;
        }

        $response = $this->getHttpClient()->request('POST', "/in.php", [
            RequestOptions::QUERY => array_merge($extra, [
                'method' => 'userrecaptcha',
                'key' => $this->apiKey,
                'googlekey' => $googleKey,
                'pageurl' => $pageUrl
            ])
        ]);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->getLogger()->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * Alias for bc
     * @param $googleKey
     * @param $pageUrl
     * @param array $extra
     * @return string
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @deprecated
     */
    public function sendRecapthaV2($googleKey, $pageUrl, $extra = [])
    {
        return $this->sendRecaptchaV2($googleKey, $pageUrl, $extra);
    }

    /**
     * Recaptcha V2 recognition.
     *
     * @param string $googleKey
     * @param string $pageUrl
     * @param array $extra              # Captcha options
     * @return string                   # Code to place in hidden form
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recognizeRecaptchaV2($googleKey, $pageUrl, $extra = [])
    {
        $captchaId = $this->sendRecaptchaV2($googleKey, $pageUrl, $extra);
        $startTime = time();

        while (true) {
            $this->getLogger()->info("Waiting {$this->rTimeout} sec.");

            sleep($this->recaptchaRTimeout);

            if (time() - $startTime >= $this->mTimeout) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $result = $this->getCaptchaResult($captchaId);

            if ($result === false) {
                continue;
            }

            $this->getLogger()->info("Elapsed " . (time()-$startTime) . " second(s).");

            return $result;
        }

        throw new RuntimeException('Unknown recognition logic error.');
    }

    /* Recaptcha v3 */

    /**
     * @param string $googleKey
     * @param string $pageUrl
     * @param string $action
     * @param string $minScore
     * @param array $extra
     * @return string
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://rucaptcha.com/blog/for_webmaster/recaptcha-v3-obhod
     */
    public function sendRecaptchaV3($googleKey, $pageUrl, $action, $minScore = '0.3', $extra = [])
    {
        $this->getLogger()->info("Try send google key (recaptcha v3)  on {$this->serverBaseUri}/in.php");

        if ($this->softId && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->softId;
        }

        $response = $this->getHttpClient()->request('POST', "/in.php", [
            RequestOptions::QUERY => array_merge($extra, [
                'method' => 'userrecaptcha',
                'version' => 'v3',
                'key' => $this->apiKey,
                'googlekey' => $googleKey,
                'pageurl' => $pageUrl,
                'action' => $action,
                'min_score' => $minScore
            ])
        ]);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->getLogger()->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * @param string $googleKey
     * @param string $pageUrl
     * @param string $action
     * @param string $minScore
     * @param array $extra
     * @return false|string
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recognizeRecaptchaV3($googleKey, $pageUrl, $action, $minScore = '0.3', $extra = [])
    {
        $captchaId = $this->sendRecaptchaV3($googleKey, $pageUrl, $action, $minScore, $extra);
        $startTime = time();

        while (true) {
            $this->getLogger()->info("Waiting {$this->rTimeout} sec.");

            sleep($this->recaptchaRTimeout);

            if (time() - $startTime >= $this->mTimeout) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $result = $this->getCaptchaResult($captchaId);

            if ($result === false) {
                continue;
            }

            $this->getLogger()->info("Elapsed " . (time()-$startTime) . " second(s).");

            return $result;
        }

        throw new RuntimeException('Unknown recognition logic error.');
    }

    /**
     * Keycaptcha recognition.
     *
     * @param string $SSCUserId
     * @param string $SSCSessionId
     * @param string $SSCWebServerSign
     * @param string $SSCWebServerSign2
     * @param string $pageUrl
     * @param array $extra
     * @return string                       # Captcha ID
     * @throws ErrorResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendKeyCaptcha(
        $SSCUserId,
        $SSCSessionId,
        $SSCWebServerSign,
        $SSCWebServerSign2,
        $pageUrl,
        $extra = []
    ) {
    
        $this->getLogger()->info("Try send google key (recaptcha)  on {$this->serverBaseUri}/in.php");

        if ($this->softId && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->softId;
        }

        $response = $this->getHttpClient()->request('POST', "/in.php", [
            RequestOptions::QUERY => array_merge($extra, [
                'method' => 'keycaptcha',
                'key' => $this->apiKey,
                's_s_c_user_id' => $SSCUserId,
                's_s_c_session_id' => $SSCSessionId,
                's_s_c_web_server_sign' => $SSCWebServerSign,
                's_s_c_web_server_sign2' => $SSCWebServerSign2,
                'pageurl' => $pageUrl
            ])
        ]);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->getLogger()->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException($this->getErrorMessage($responseText) ?: "Unknown error: `{$responseText}`.");
    }

    /**
     * Keycaptcha recognition.
     *
     * @param string $SSCUserId
     * @param string $SSCSessionId
     * @param string $SSCWebServerSign
     * @param string $SSCWebServerSign2
     * @param string $pageUrl
     * @param array $extra
     * @return string                       # Code to place into id="capcode" input value
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recognizeKeyCaptcha(
        $SSCUserId,
        $SSCSessionId,
        $SSCWebServerSign,
        $SSCWebServerSign2,
        $pageUrl,
        $extra = []
    ) {
        $captchaId = $this
            ->sendKeyCaptcha($SSCUserId, $SSCSessionId, $SSCWebServerSign, $SSCWebServerSign2, $pageUrl, $extra);

        $startTime = time();

        while (true) {
            $this->getLogger()->info("Waiting {$this->rTimeout} sec.");

            sleep($this->recaptchaRTimeout);

            if (time() - $startTime >= $this->mTimeout) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $result = $this->getCaptchaResult($captchaId);

            if ($result === false) {
                continue;
            }

            $this->getLogger()->info("Elapsed " . (time()-$startTime) . " second(s).");

            return $result;
        }

        throw new RuntimeException('Unknown recognition logic error.');
    }

    /**
     * Override generic method for using json response.
     *
     * @param string $captchaId # Captcha task ID
     * @return false|string             # Solved captcha text or false if captcha is not ready
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCaptchaResult($captchaId)
    {
        $response = $this
            ->getHttpClient()
            ->request('GET', "/res.php?key={$this->apiKey}&action=get&id={$captchaId}&json=1");

        $responseData = json_decode($response->getBody()->__toString(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(
                'json_decode error: ' . json_last_error_msg()
            );
        }

        if ($responseData['status'] === self::STATUS_CODE_CAPCHA_NOT_READY) {
            return false;
        }

        if ($responseData['status'] === self::STATUS_CODE_OK) {
            $this->getLogger()->info("Got OK response: `{$responseData['request']}`.");
            return $responseData['request'];
        }

        throw new ErrorResponseException(
            $this->getErrorMessage(
                $responseData['request']
            ) ?: $responseData['request'],
            $responseData['status']
        );
    }

    /**
     * Match error code by response.
     *
     * @param string $responseText
     * @return int
     */
    private function getErrorCode($responseText)
    {
        if (preg_match('/ERROR:\s*(\d{0,4})/ui', $responseText, $matches)) {
            return intval($matches[1]);
        }
        return 0;
    }
}

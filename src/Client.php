<?php

namespace Rucaptcha;

use GuzzleHttp\Psr7\Request;
use Rucaptcha\Exception\ErrorResponseException;
use Rucaptcha\Exception\InvalidArgumentException;
use Rucaptcha\Exception\RuntimeException;
use Throwable;

/**
 * Class Client
 *
 * @package Rucaptcha
 * @author Dmitry Gladyshev <deel@email.ru>
 */
final class Client extends GenericClient
{
    /* json status codes */
    const STATUS_CODE_CAPCHA_NOT_READY = 0;
    const STATUS_CODE_OK = 1;

    /* status codes */
    const STATUS_OK_REPORT_RECORDED = 'OK_REPORT_RECORDED';
    

    /**
     * @inheritdoc
     */
    public function sendCaptcha($content, array $extra = []): string
    {
        if ($this->config->getSoftId() && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->config->getSoftId();
        }
        return parent::sendCaptcha($content, $extra);
    }

    /**
     * Bulk captcha result.
     *
     * @param int[] $captchaIds # Captcha task Ids array
     * @return string[]                 # Array $captchaId => $captchaText or false if is not ready
     * @throws Throwable
     */
    public function getCaptchaResultBulk(array $captchaIds): array
    {
        $request = new Request('GET', $this->config->getServerBaseUri() . '/res.php?' . http_build_query([
            'key' => $this->config->getApiKey(),
            'action' => 'get',
            'ids' => implode(',', $captchaIds)
        ]));

        $response = $this->httpClient->sendRequest($request);

        $captchaTexts = $response->getBody()->__toString();

        $this->logger->info("Got bulk response: `{$captchaTexts}`.");

        $captchaTexts = explode("|", $captchaTexts);

        $result = [];

        foreach ($captchaTexts as $index => $captchaText) {
            $captchaText = html_entity_decode(trim($captchaText));
            $result[$captchaIds[$index]] = ($captchaText == self::STATUS_CAPTCHA_NOT_READY) ? false : $captchaText;
        }

        return $result;
    }

    /**
     * Returns balance of account.
     *
     * @return string
     * @throws Throwable
     */
    public function getBalance(): string
    {
        $request = new Request('GET', $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=getbalance");

        $response = $this->httpClient->sendRequest($request);

        return $response->getBody()->__toString();
    }


    /**
     * Report of wrong recognition.
     *
     * @param string $captchaId
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function reportBad(string $captchaId): void
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=reportbad&id={$captchaId}"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK_REPORT_RECORDED) {
            return;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }


    /**
     * Reports rucaptcha for good recognition.
     *
     * @param $captchaId
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function reportGood($captchaId): void
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=reportgood&id={$captchaId}"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK_REPORT_RECORDED) {
            return;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * @param string $captchaId     # Captcha task ID
     * @return array | false        # Solved captcha and cost array or false if captcha is not ready
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function getCaptchaResultWithCost(string $captchaId): array
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=get2&id={$captchaId}"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_CAPTCHA_NOT_READY) {
            return [];
        }

        if (strpos($responseText, 'OK|') !== false) {
            $this->logger->info("Got OK response: `{$responseText}`.");
            $data = explode('|', $responseText);
            return [
                'captcha' => html_entity_decode(trim($data[1])),
                'cost' => html_entity_decode(trim($data[2])),
            ];
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Add pingback url to rucaptcha whitelist.
     *
     * @param string $url
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function addPingback(string $url): void
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=add_pingback&addr={$url}"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK) {
            return;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Returns pingback whitelist items.
     *
     * @return string[]                 # List of urls
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function getPingbacks(): array
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=get_pingback"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $data = explode('|', $responseText);
            unset($data[0]);
            return empty($data[1]) ? [] : array_values($data);
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Remove pingback url from whitelist.
     *
     * @param string $uri
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function deletePingback(string $uri): void
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=del_pingback&addr={$uri}"
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if ($responseText === self::STATUS_OK) {
            return;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? $responseText,
            $this->getErrorCode($responseText) ?: 0
        );
    }

    /**
     * Truncate pingback whitelist.
     *
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function deleteAllPingbacks(): void
    {
        $this->deletePingback('all');
    }

    /* Recaptcha v2 */

    /**
     * Sent recaptcha v2
     *
     * @param string $googleKey
     * @param string $pageUrl
     * @param array $extra
     *
     * @return string
     *
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function sendRecaptchaV2(
        string $googleKey,
        string $pageUrl,
        array $extra = []
    ): string {
        $this->logger->info("Try send google key (recaptcha)  on {$this->config->getServerBaseUri()}/in.php");

        if ($this->config->getSoftId() && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->config->getSoftId();
        }

        $params = array_merge($extra, [
            'method' => 'userrecaptcha',
            'key' => $this->config->getApiKey(),
            'googlekey' => $googleKey,
            'pageurl' => $pageUrl
        ]);

        $request = new Request(
            'POST',
            $this->config->getServerBaseUri() . "/in.php?" . http_build_query($params)
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->logger->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? "Unknown error: '{$responseText}'."
        );
    }

    /**
     * Recaptcha V2 recognition.
     *
     * @param string $googleKey
     * @param string $pageUrl
     * @param array $extra              # Captcha options
     *
     * @return string                   # Code to place in hidden form
     *
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function recognizeRecaptchaV2(
        string $googleKey,
        string $pageUrl,
        array $extra = []
    ): string {
        $captchaId = $this->sendRecaptchaV2($googleKey, $pageUrl, $extra);
        $startTime = time();

        while (true) {
            $this->logger->info("Waiting {$this->config->getRecaptchaRTimeout()} sec.");

            sleep($this->config->getRecaptchaRTimeout());

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

    /* Recaptcha v3 */

    /**
     * @param string $googleKey
     * @param string $pageUrl
     * @param string $action
     * @param string $minScore
     * @param array $extra
     *
     * @return string
     *
     * @throws ErrorResponseException
     * @throws Throwable
     *
     * @see https://rucaptcha.com/blog/for_webmaster/recaptcha-v3-obhod
     */
    public function sendRecaptchaV3(
        string $googleKey,
        string $pageUrl,
        string $action,
        string $minScore = '0.3',
        array $extra = []
    ): string {
        $this->logger->info("Try send google key (recaptcha v3)  on {$this->config->getServerBaseUri()}/in.php");

        if (
            $this->config->getSoftId()
            && !isset($extra[Extra::SOFT_ID])
        ) {
            $extra[Extra::SOFT_ID] = $this->config->getSoftId();
        }

        $request = new Request(
            'POST',
            $this->config->getServerBaseUri() . "/in.php?". http_build_query(array_merge($extra, [
                'method' => 'userrecaptcha',
                'version' => 'v3',
                'key' => $this->config->getApiKey(),
                'googlekey' => $googleKey,
                'pageurl' => $pageUrl,
                'action' => $action,
                'min_score' => $minScore
            ]))
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->logger->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException(Error::$messages[$responseText] ?? "Unknown error: '{$responseText}'.");
    }

    /**
     * @param string $googleKey
     * @param string $pageUrl
     * @param string $action
     * @param string $minScore
     * @param array $extra
     *
     * @return string
     *
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function recognizeRecaptchaV3(
        string $googleKey,
        string $pageUrl,
        string $action,
        string $minScore = '0.3',
        array $extra = []
    ): string {
        $captchaId = $this->sendRecaptchaV3($googleKey, $pageUrl, $action, $minScore, $extra);
        $startTime = time();

        while (true) {
            $this->logger->info("Waiting {$this->config->getRecaptchaRTimeout()} sec.");

            sleep($this->config->getRecaptchaRTimeout());

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
     * Keycaptcha recognition.
     *
     * @param string $SSCUserId
     * @param string $SSCSessionId
     * @param string $SSCWebServerSign
     * @param string $SSCWebServerSign2
     * @param string $pageUrl
     * @param array $extra
     *
     * @return string # Captcha ID
     *
     * @throws ErrorResponseException
     * @throws Throwable
     */
    public function sendKeyCaptcha(
        string $SSCUserId,
        string $SSCSessionId,
        string $SSCWebServerSign,
        string $SSCWebServerSign2,
        string $pageUrl,
        array $extra = []
    ): string {
        $this->logger->info("Try send google key (recaptcha)  on {$this->config->getServerBaseUri()}/in.php");

        if ($this->config->getSoftId() && !isset($extra[Extra::SOFT_ID])) {
            $extra[Extra::SOFT_ID] = $this->config->getSoftId();
        }

        $request = new Request(
            'POST',
            $this->config->getServerBaseUri() . "/in.php?" . http_build_query(array_merge($extra, [
                'method' => 'keycaptcha',
                'key' => $this->config->getApiKey(),
                's_s_c_user_id' => $SSCUserId,
                's_s_c_session_id' => $SSCSessionId,
                's_s_c_web_server_sign' => $SSCWebServerSign,
                's_s_c_web_server_sign2' => $SSCWebServerSign2,
                'pageurl' => $pageUrl
            ]))
        );

        $response = $this->httpClient->sendRequest($request);

        $responseText = $response->getBody()->__toString();

        if (strpos($responseText, 'OK|') !== false) {
            $this->lastCaptchaId = explode("|", $responseText)[1];
            $this->logger->info("Sending success. Got captcha id `{$this->lastCaptchaId}`.");
            return $this->lastCaptchaId;
        }

        throw new ErrorResponseException(
            Error::$messages[$responseText] ?? "Unknown error: '{$responseText}'."
        );
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
     * @throws Throwable
     */
    public function recognizeKeyCaptcha(
        string $SSCUserId,
        string $SSCSessionId,
        string $SSCWebServerSign,
        string $SSCWebServerSign2,
        string $pageUrl,
        array $extra = []
    ): string {
        $captchaId = $this
            ->sendKeyCaptcha($SSCUserId, $SSCSessionId, $SSCWebServerSign, $SSCWebServerSign2, $pageUrl, $extra);

        $startTime = time();

        while (true) {
            $this->logger->info("Waiting {$this->config->getRecaptchaRTimeout()} sec.");

            sleep($this->config->getRecaptchaRTimeout());

            if (time() - $startTime >= $this->config->getMTimeout()) {
                throw new RuntimeException("Captcha waiting timeout.");
            }

            $result = $this->getCaptchaResult($captchaId);

            if ($result === false) {
                continue;
            }

            $this->logger->info("Elapsed " . (time()-$startTime) . " second(s).");

            return $result;
        }
    }

    /**
     * Override generic method for using json response.
     *
     * @param string $captchaId # Captcha task ID
     * @return null|string             # Solved captcha text or false if captcha is not ready
     * @throws ErrorResponseException
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function getCaptchaResult(string $captchaId): ?string
    {
        $request = new Request(
            'GET',
            $this->config->getServerBaseUri() . "/res.php?key={$this->config->getApiKey()}&action=get&id={$captchaId}&json=1"
        );

        $response = $this->httpClient->sendRequest($request);

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
            $this->logger->info("Got OK response: `{$responseData['request']}`.");
            return $responseData['request'];
        }

        throw new ErrorResponseException(
            Error::$messages[$responseData['request']] ?? $responseData['request'],
            $responseData['status']
        );
    }

    /**
     * Match error code by response.
     *
     * @param string $responseText
     * @return int
     */
    private function getErrorCode(string $responseText): int
    {
        if (preg_match('/ERROR:\s*(\d{0,4})/ui', $responseText, $matches)) {
            return intval($matches[1]);
        }
        return 0;
    }
}

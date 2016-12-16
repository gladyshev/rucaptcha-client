<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;


use Rucaptcha\Exception\RuntimeException;

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
     * @return string
     */
    public function getBalance()
    {
        $response = $this->getHttpClient()->request('GET', "/res.php?key={$this->apiKey}&action=getbalance");
        return $response->getBody()->__toString();
    }

    /**
     * @param $captchaId
     * @return bool
     * @throws RuntimeException
     */
    public function badCaptcha($captchaId)
    {
        $response = $this->getHttpClient()->request('GET', "/res.php?key={$this->apiKey}&action=reportbad&id={$captchaId}");
        if ($response->getBody()->__toString() === self::STATUS_OK_REPORT_RECORDED) {
            return true;
        }
        throw new RuntimeException('Report sending trouble: ' . $response->getBody() . '.');
    }

    /**
     * @param array $paramsList
     * @return array
     */
    public function getLoad(array $paramsList = ['waiting', 'load', 'minbid', 'averageRecognitionTime'])
    {
        $response = $this->getHttpClient()->request('GET', "/load.php");
        $responseText = $response->getBody()->__toString();
        $statusData = [];

        foreach ($paramsList as $item) {
            // Fast parse tags
            $value = substr($responseText,
                strpos($responseText, '<' . $item . '>') + mb_strlen('<' . $item . '>'),
                strpos($responseText, '</' . $item . '>') - strpos($responseText, '<' . $item . '>') - mb_strlen('<' . $item . '>')
            );

            if ($value !== false) {
                $statusData[$item] = $value;
            }
        }
        return $statusData;
    }
}

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
     * @var string
     */
    private $softId = '1013';

    /**
     * @inheritdoc
     */
    public function sendCaptcha($content, array $extra = [])
    {
        if ($this->softId) {
            $extra[Extra::SOFT_ID] = $this->softId;
        }
        return parent::sendCaptcha($content, $extra);
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
        $response = $this->getHttpClient()->request('GET', "/res.php?action=reportbad&id={$captchaId}");
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
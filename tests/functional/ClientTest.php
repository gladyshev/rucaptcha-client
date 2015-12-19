<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha\tests\functional;


use Rucaptcha\Client;
use Rucaptcha\Extra;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $yandexCaptchaImage;

    /**
     * @var string
     */
    protected $yandexCaptchaText;

    /**
     * @var string
     */
    protected $seopultCaptchaImage;

    /**
     * @var string
     */
    protected $seopultCaptchaText;


    public function setUp()
    {
        $key = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR .'apikey');

        $this->yandexCaptchaImage = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'yandex_captcha.gif';
        $this->yandexCaptchaText  = "915427";

        $this->seopultCaptchaImage =  __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'seopult_captcha.png';
        $this->seopultCaptchaText = 'DZTY';

        $this->client = new Client($key, [
            'verbose' => true
        ]);
    }


    public function testYandexCaptchaRecognition()
    {
        $recognizedText = $this->client->recognizeFile($this->yandexCaptchaImage, [
            Extra::REGSENSE => 0
        ]);

        $this->assertEquals($this->yandexCaptchaText, $recognizedText);
    }


    public function testSeopultCaptchaRecognition()
    {
        $recognizedText = $this->client->recognizeFile($this->seopultCaptchaImage, [
            Extra::REGSENSE => 1
        ]);

        $this->assertEquals($this->seopultCaptchaText, $recognizedText);
    }


    public function testLoadStatistics()
    {
        $data = $this->client->getLoad();
        $this->assertArrayHasKey('waiting', $data);
        $this->assertArrayHasKey('load', $data);
        $this->assertArrayHasKey('minbid', $data);
        $this->assertArrayHasKey('averageRecognitionTime', $data);
    }


    public function testGetAccountBalance()
    {
        $data = $this->client->getBalance();
        $this->assertNotEmpty($data);
    }
}

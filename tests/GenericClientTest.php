<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha\tests;


use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Rucaptcha\Error;
use Rucaptcha\GenericClient;

class GenericClientTest extends \PHPUnit_Framework_TestCase
{
    public function testGetHttpClientMustReturnClientInterfaceInstance()
    {
        $client = new GenericClient('');
        $httpClient = $this->invokeMethod($client, 'getHttpClient');
        $this->assertInstanceOf(ClientInterface::class, $httpClient);
    }

    public function testGetLoggerMustReturnLoggerInterfaceInstance()
    {
        $client = new GenericClient('');
        $logger = $this->invokeMethod($client, 'getLogger');
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testEmptyCaptchaIdBeforeFirstSendCaptchaTask()
    {
        $client = new GenericClient('');
        $this->assertEquals('', $client->getLastCaptchaId());
    }

    /**
     * @param string $response
     * @dataProvider providerCorrectInResponse
     */
    public function testSendCaptchaMustReturnCaptchaIdAndStoreLastCaptchaId($response)
    {
        $client = self::buildClientWithMockedGuzzle($response);
        $serverResponse = $client->sendCaptcha('');
        $this->assertEquals('1234567890', $serverResponse);
        $this->assertEquals('1234567890', $client->getLastCaptchaId());
    }

    /**
     * @param string $response
     * @dataProvider providerCorrectResResponse
     */
    public function testGetCaptchaResultReturnSolvedStringOrFalse($response)
    {
        $client = self::buildClientWithMockedGuzzle($response);

        $serverResponse = $client->getCaptchaResult('');

        if($response==='CAPCHA_NOT_READY') {
            $this->assertFalse($serverResponse);
        } else {
            $this->assertEquals('1234567890', $serverResponse);
        }
    }

    /**
     * @param $errorResponse
     * @dataProvider providerErrorInResponse
     * @expectedException \Rucaptcha\Exception\RuntimeException
     */
    public function testSendCaptchaMustThrowRucaptchaExceptionOnErrorResponse($errorResponse)
    {
        $client = self::buildClientWithMockedGuzzle($errorResponse);
        $client->sendCaptcha('');
    }

    /**
     * @param $errorResponse
     * @dataProvider providerErrorResResponse
     * @expectedException \Rucaptcha\Exception\RuntimeException
     */
    public function testGetCaptchaResultMustThrowRucaptchaExceptionOnErrorResponse($errorResponse)
    {
        $client = self::buildClientWithMockedGuzzle($errorResponse);
        $client->getCaptchaResult('');
    }

    /* Data providers */

    public function providerCorrectInResponse()
    {
        return [
            ['OK|1234567890']
        ];
    }

    public function providerCorrectResResponse()
    {
        return [
            ['OK|1234567890'],
            ['CAPCHA_NOT_READY']
        ];
    }

    public function providerErrorInResponse()
    {
        return [
            [Error::KEY_DOES_NOT_EXIST],
            [Error::WRONG_USER_KEY],
            [Error::ZERO_BALANCE],
            [Error::NO_SLOT_AVAILABLE],
            [Error::ZERO_CAPTCHA_FILESIZE],
            [Error::TOO_BIG_CAPTCHA_FILESIZE],
            [Error::WRONG_FILE_EXTENSION],
            [Error::IMAGE_TYPE_NOT_SUPPORTED],
            [Error::IP_NOT_ALLOWED],
            [Error::IP_BANNED],
            ["<html><head></head><body><h1>Page Not Found 404</h1></body></html>"],
            ["ERROR_NEW_SERVICE_ERROR_TYPE"]
        ];
    }

    public function providerErrorResResponse()
    {
        return [
            [Error::WRONG_ID_FORMAT],
            [Error::KEY_DOES_NOT_EXIST],
            [Error::CAPTCHA_UNSOLVABLE],
            [Error::WRONG_CAPTCHA_ID],
            [Error::BAD_DUPLICATES],
            ["<html><head></head><body><h1>Page Not Found 404</h1></body></html>"],
            ["ERROR_NEW_SERVICE_ERROR_TYPE"]
        ];
    }

    /* Helpers */

    public static function buildClientWithMockedGuzzle($response)
    {
        $mock = new MockHandler([
            new Response(200, [], $response)
        ]);

        $handler = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handler]);

        $genericClient = new GenericClient('');
        $genericClient->setHttpClient($httpClient);

        return $genericClient;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}

<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha\Test;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Rucaptcha\Config;
use Rucaptcha\Error;
use Rucaptcha\GenericClient;

class GenericClientTest extends TestCase
{

    public function testEmptyCaptchaIdBeforeFirstSendCaptchaTask()
    {
        $client = new GenericClient(
            new Config(''),
            new GuzzleClient()
        );
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
            $this->assertNull($serverResponse);
        } else {
            $this->assertEquals('1234567890', $serverResponse);
        }
    }

    /**
     * @param $errorResponse
     * @dataProvider providerErrorInResponse
     */
    public function testSendCaptchaMustThrowRucaptchaExceptionOnErrorResponse($errorResponse)
    {
        $this->expectException(\Rucaptcha\Exception\RuntimeException::class);
        $client = self::buildClientWithMockedGuzzle($errorResponse);
        $client->sendCaptcha('');
    }

    /**
     * @param $errorResponse
     * @dataProvider providerErrorResResponse
     */
    public function testGetCaptchaResultMustThrowRucaptchaExceptionOnErrorResponse($errorResponse)
    {
        $client = self::buildClientWithMockedGuzzle($errorResponse);
        $this->expectException(\Rucaptcha\Exception\RuntimeException::class);
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

        return new GenericClient(
            new Config(''),
            $httpClient
        );
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

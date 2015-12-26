<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha\tests\unit;


use Rucaptcha\GenericClient;

class GenericClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Rucaptcha\Exception\InvalidArgumentException
     */
    public function testInvalidArgumentExceptionOnSetInvalidOptions()
    {
        $client = new GenericClient('');
        $client->setOptions([
            'imAnIncorrectOption' => 100500
        ]);
    }

    public function testEmptyCaptchaIdBeforeFirstSendCaptchaTask()
    {
        $client = new GenericClient('');
        $this->assertEquals('', $client->getLastCaptchaId());
    }
}

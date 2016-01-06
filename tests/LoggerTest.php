<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha\tests;


use PHPUnit_Framework_TestCase;
use Psr\Log\LogLevel;
use Rucaptcha\Logger;

class LoggerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerLevelMessages
     */
    public function testPrintingLogInStdOutIfVerboseFlag($level, $message)
    {
        $logger = new Logger;
        $logger->verbose = true;

        $logger->log($level, $message);

        $this->expectOutputRegex("#\[{$level}\]\s{$message}#ui");
    }

    /**
     * @dataProvider providerLevelMessages
     */
    public function testDoNotPrintingInStdOutWithoutVerboseFlag($level, $message)
    {
        $logger = new Logger;
        $logger->verbose = false;
        $logger->log($level, $message);
        $this->expectOutputString('');
    }

    public function providerLevelMessages()
    {
        return [
            [LogLevel::ALERT, 'hello, i am the Log Entry!'],
            [LogLevel::CRITICAL, 'hello, i am the Log Entry!'],
            [LogLevel::EMERGENCY, 'hello, i am the Log Entry!'],
            [LogLevel::DEBUG, 'hello, i am the Log Entry!'],
            [LogLevel::ERROR, 'hello, i am the Log Entry!'],
            [LogLevel::INFO, 'hello, i am the Log Entry!'],
            [LogLevel::WARNING, 'hello, i am the Log Entry!']
        ];
    }
}
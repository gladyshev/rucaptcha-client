<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        echo date("d/m/y H:i:s", time()) . ' [' . $level . '] ' . $message . PHP_EOL;
    }
}

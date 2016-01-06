<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;


use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /**
     * @var bool
     */
    public $verbose = false;

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->verbose)
        {
            echo date("d/m/y H:i:s") . ' ['.$level.'] ' . $message . PHP_EOL;
        }
    }
}
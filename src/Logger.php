<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;


use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    /**
     * @var bool
     */
    private $verbose;

    /**
     * Logger constructor.
     * @param bool $verbose
     */
    public function __construct(&$verbose)
    {
        $this->verbose =& $verbose;
    }

    public function log($level, $message, array $context = [])
    {
        if ($this->verbose)
        {
            $entry = date("d/m/y H:i:s").' ['.$level.'] '.$message.PHP_EOL;

            file_put_contents('php://stdout', $entry);

            if ($level === LogLevel::ERROR) {
                // file_put_contents('php://stderr', $entry);
            }
        }
    }
}
<?php

namespace Eva\EvaEngine\Error;

// +----------------------------------------------------------------------
// | [phalcon]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-7-16 18:55
// +----------------------------------------------------------------------
// + CLIErrorHandler.php
// +----------------------------------------------------------------------
use Eva\EvaEngine\CLI\Output\ConsoleOutput;
use Eva\EvaEngine\CLI\Output\StreamOutput;
use Eva\EvaEngine\CLI\Formatter\OutputFormatterInterface;
use Phalcon\DI;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\AdapterInterface as LoggerInterface;

class CLIErrorHandler implements ErrorHandlerInterface
{
    static protected $logger = false;

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!($errno & error_reporting())) {
            return;
        }
        $output = new ConsoleOutput();
        $output->writeln("");
        $output->writelnWarning(' [WARNING]: '. $errstr.' in file '. $errfile .' at line '.$errline);
        $output->writeln("");

    }

    public static function exceptionHandler(\Exception $e)
    {
        $output = new ConsoleOutput();
        $output->writelnError($e->getMessage());
        $output->writelnComment($e->getTraceAsString());

    }

    public static function shutdownHandler()
    {
//        var_dump('shutdownHandler');

    }
    public static function getLogger()
    {
        if (static::$logger !== false) {
            return static::$logger;
        }

        $di = DI::getDefault();
        $config = $di->get('config');

        if (!isset($config->error->disableLog) ||
            (isset($config->error->disableLog) && $config->error->disableLog) ||
            empty($config->error->logPath)
        ) {
            return static::$logger = null;
        }

        static::$logger = new FileLogger($config->error->logPath . '/' . 'system_error_' . date('Ymd') . '.log');

        return static::$logger;
    }

    public static function setLogger(LoggerInterface $logger)
    {
        static::$logger = $logger;
        return self;
    }

    protected static function logError(Error $error)
    {
        $logger = static::getLogger();
        if (!$logger) {
            return;
        }

        return $logger->log($error);
    }

    protected static function errorProcess(Error $error)
    {

        static::logError($error);


        $useErrorController = false;

        if ($error->isException()) {
            $useErrorController = true;
        } else {
            switch ($error->type()) {
                case E_WARNING:
                case E_NOTICE:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                case E_STRICT:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                case E_ALL:
                    break;
                default:
                    $useErrorController = true;
            }
        }

        if (!$useErrorController) {
            return;
        }


    }
}

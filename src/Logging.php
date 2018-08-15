<?php

namespace RedisQueue;

/**
 * 日志类
 * Class Logging
 */
class Logging
{
    // 文件路径
    private $logFile = '';
    // 日志记录格式，时间 类型 内容
    private $formatter = "%s\t%s\t%s\n";

    public function __construct($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $this->logFile = $path . '/' . date("Ymd") . '.log';
    }

    public function log($message, $type = 'info')
    {
        $message = is_array($message) ? json_encode($message) : $message;
        $log = sprintf($this->formatter, date('Y-m-d H:i:s'), $type, $message);
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }

    public function info($message)
    {
        $this->log($message);
    }

    public function warn($message)
    {
        $this->log($message, 'warn');
    }

    public function error($message)
    {
        $this->log($message, 'error');
    }

    public function notice($message)
    {
        $this->log($message, 'notice');
    }
}
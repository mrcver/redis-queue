<?php

namespace RedisQueue;

/**
 * Class RedisInstance
 * redis 单例类
 * @package RedisQueue
 */
class RedisInstance
{
    static private $_instance = null;

    /**
     * RedisInstance constructor.
     * 实例化优先使用到以下常量作为redis连接配置
     * @param string $host
     * @param int $port
     * @param null $password
     * @param int $database
     */
    private final function __construct($host, $port, $database, $password)
    {
        $host = (defined('REDIS_HOST') && REDIS_HOST) ? REDIS_HOST : $host;
        $port = (defined('REDIS_PORT') && REDIS_PORT) ? REDIS_PORT : $port;
        $password = (defined('REDIS_AUTH') && REDIS_AUTH) ? REDIS_AUTH : $password;
        $database = (defined('REDIS_DATABASE') && REDIS_DATABASE) ? REDIS_DATABASE : $database;
        self::$_instance = new \Redis();
        self::$_instance->connect($host, $port);
        self::$_instance->auth($password);
        self::$_instance->select($database);
    }

    private function __clone()
    {
    }

    /**
     * 获取redis单例对象
     * @param string $host
     * @param int $port
     * @param null $password
     * @param int $database
     * @return null|\Redis
     */
    static public function getInstance(
        $host = '127.0.0.1',
        $port = 6379,
        $database = 0,
        $password = null
    )
    {
        if (!(self::$_instance instanceof \Redis)) {
            new RedisInstance($host, $port, $database, $password);
        }
        return self::$_instance;
    }
}
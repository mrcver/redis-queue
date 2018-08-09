<?php

namespace RedisQueue\Traits;

/**
 * redis 队列的相关配置属性
 * Trait RedisTrait
 * @package RedisQueue\Traits
 */
trait RedisTrait
{
    private $redis = null;
    private $list = 'redis-queue:task';
    private $failedList = 'redis-queue:failed';
    private $processList = 'redis-queue:process';
}
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
    private $list = 'redis-queue:task:';
    private $failedList = 'redis-queue:failed:';
    private $processList = 'redis-queue:process:';
    private $lockList = 'redis-queue:locked:';
    private $queueName = 'default';

    private function init(\Redis $redis, $queueName = 'default')
    {
        $this->redis = $redis;
        $this->queueName = $queueName;
        $this->list .= $queueName;
        $this->failedList .= $queueName;
        $this->processList .= $queueName;
        $this->lockList .= $queueName;
    }
}
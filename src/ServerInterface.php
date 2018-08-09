<?php

namespace RedisQueue;

/**
 * Interface ServerInterface
 * 消息队列任务执行接口
 * @package RedisQueue
 */
interface ServerInterface {
    const VERSION = '1.0';

    /**
     * 执行任务方法
     * @return mixed
     */
    public function process();
}
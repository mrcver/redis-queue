<?php

namespace RedisQueue;

use RedisQueue\JobInterface as Job;

/**
 * Interface ClientInterface
 * 任务投递接口
 * @package RedisQueue
 */
interface ClientInterface
{
    const VERSION = "1.0";

    /**
     * 任务投递方法
     * @param JobInterface $job
     * @param int $maxTries 最大尝试次数
     * @return mixed
     */
    public function dispatch(Job $job, int $maxTries);
}
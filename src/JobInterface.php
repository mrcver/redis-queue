<?php

namespace RedisQueue;

/**
 * Interface JobInterface
 * 任务接口，投递到消息队列的任务需实现此类
 * @package RedisQueue
 */
interface JobInterface
{
    //执行任务
    public function handle();
}
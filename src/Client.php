<?php

namespace RedisQueue;

use RedisQueue\JobInterface as Job;
use RedisQueue\Traits\RedisTrait;

/**
 * Class Client
 * 消息队列投递实现
 * @package RedisQueue
 */
class Client implements ClientInterface
{
    use RedisTrait;

    /**
     * Client constructor.
     * @param \Redis $redis
     * @param string $queueName
     */
    public function __construct(\Redis $redis, $queueName = 'default')
    {
        // 初始化配置
        $this->init($redis, $queueName);
    }

    /**
     * 分配任务
     * @param JobInterface $job
     * @param int $maxTries
     * @return mixed|void
     */
    public function dispatch(Job $job, int $maxTries = 0)
    {
        $data = [
            'name' => get_class($job),
            'job' => serialize($job),
            'maxTries' => $maxTries,
            'runTimes' => 0,
            'deliveryTime' => date('Y-m-d H:i:s'),
        ];
        $this->setJob($data);
    }

    /**
     * 添加任务redis queue
     * @param $data
     */
    private function setJob($data)
    {
        $data = json_encode($data);
        $this->redis->lPush($this->list, $data);
    }

    public function test()
    {
        echo 'this is a test function' . PHP_EOL;
    }
}

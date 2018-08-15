<?php

namespace RedisQueue;

use Exception;
use RedisQueue\Traits\RedisTrait;
use RedisQueue\Traits\WorkerTrait;
use Throwable;

/**
 * Class Server
 * 队列任务处理类
 * @package RedisQueue
 */
class Worker implements WorkerInterface
{
    use RedisTrait, WorkerTrait;

    /**
     * Server constructor.
     * @param \Redis $redis redis 对象实例
     * @param string $queueName
     * @param null $jobPath 需要实现自动加载任务类时传参
     * @param string $jobExt
     */
    public function __construct(\Redis $redis, $logDir, $queueName = 'default', $jobPath = null, $jobExt = '.php')
    {
        // 初始化配置
        $this->init($redis, $queueName);

        // 对于未使用composer加载的情况实现任务类文件自动加载
        $this->__autoload($jobPath, $jobExt);

        $this->logging = new Logging($logDir);
    }


    /**
     * 处理队列
     * @return mixed|void
     */
    public function process()
    {
        try {
            $this->logging->info("working...");
            while (true) {
                $data = $this->getJob();
                if ($data) {
                    $obj = unserialize($data['job']);
                    $obj->handle();
                } else {
                    // 队列中无任务时等待3秒再继续
                    sleep(3);
                }
            }
        } catch (Exception $e) {
            $this->errorHandle($data, $e);
        } catch (Throwable $e) {
            $this->errorHandle($data, $e);
        }
    }
}

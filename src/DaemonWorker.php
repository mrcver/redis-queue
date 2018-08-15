<?php

namespace RedisQueue;

use Exception;
use RedisQueue\Traits\RedisTrait;
use RedisQueue\Traits\WorkerTrait;
use Throwable;

/**
 * Class DaemonWorker
 * 任务后台运行
 * @package RedisQueue
 */
class DaemonWorker extends Daemon implements WorkerInterface
{
    use RedisTrait, WorkerTrait;
    private $lock = 'N'; //任务锁
    private $writeLock = 'N'; //任务锁定写入redis

    public function __construct(\Redis $redis, $logDir, $queueName = 'default', $jobPath = null, $jobExt = '.php')
    {
        parent::__construct();
        // 初始化配置
        $this->init($redis, $queueName);

        // 对于未使用composer加载的情况实现任务类文件自动加载
        $this->__autoload($jobPath, $jobExt);

        // 初始化日志文件
        $this->logging = new Logging($logDir);
        $this->isDaemon = true;
    }

    /**
     * 清空锁定任务
     */
    protected function flushLocked()
    {
        $this->redis->del($this->lockList);
    }

    /**
     * @param null $workNums
     */
    protected function start($workNums = null)
    {
        $this->logging->notice("Starting...");
        $this->flushLocked();
        parent::start($workNums);
    }

    /**
     * 处理队列任务
     * @return mixed|void
     */
    public function process()
    {
        try {
            if ($this->lock == 'N') {
                $data = $this->getJob();
                if ($data) {
                    $obj = unserialize($data['job']);
                    $obj->handle();
                } else {
                    // 队列中无任务时等待3秒再继续
                    sleep(3);
                }
            } else {
                if ($this->writeLock == 'N') {
                    $this->redis->hSet($this->lockList, md5($this->curPid), $this->curPid);
                    $this->writeLock = 'Y';
                }
                sleep(3);
            }
        } catch (Exception $e) {
            $this->errorHandle($data, $e);
        } catch (Throwable $e) {
            $this->errorHandle($data, $e);
        }
    }

    /**
     * daemon work方法
     */
    public function work()
    {
        pcntl_signal(SIGHUP, [&$this, "lockWork"]);
        $this->logging->info("Working...");
        while (true) {
            pcntl_signal_dispatch();
            $this->process();
        }
    }

    /**
     * 进程锁定信号处理
     */
    protected function lockWork()
    {
        $this->logging->info("pid:{$this->curPid} 接受到信号");
        $this->logging->info($this->getStatus());
        $this->lock();
        $this->logging->info($this->getStatus());
        $this->logging->info("信号处理完成");
    }

    protected function getStatus()
    {
        return $this->lock;
    }

    protected function lock()
    {
        $this->lock = 'Y';
    }

    protected function unlock()
    {
        $this->lock = 'N';
    }

    /**
     * 获取已经锁定任务进程
     * @return mixed
     */
    protected function getLockList()
    {
        return $this->redis->hGetAll($this->lockList);
    }

    /**
     * 重启所有daemon后台进程
     */
    protected function restart()
    {
        echo "Restarting ...", PHP_EOL;
        $this->logging->info('Restarting ...');
        $pids = file($this->pid);
        foreach ($pids as $pid) {
            posix_kill($pid, SIGHUP);
        }
        while (true) {
            $lockList = $this->getLockList();
            if ($lockList && count($lockList) == count($pids)) {
                break;
            }
        }
        $workNums = $this->stop();
        $this->start($workNums);
    }

    protected function stop(){
        parent::stop();
        $this->logging->notice("Stopped");
    }
}

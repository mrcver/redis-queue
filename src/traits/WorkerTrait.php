<?php

namespace RedisQueue\Traits;

/**
 * Class Worker
 * @package RedisQueue\Traits
 */
trait WorkerTrait
{
    private $logging = null;
    private $isDaemon = false;

    /**
     * 自动加载实现
     * @param null $jobPath
     * @param string $jobExt
     */
    private function __autoload($jobPath = null, $jobExt = '.php')
    {
        if ($jobPath && is_dir($jobPath) && $jobExt) {
            set_include_path(get_include_path() . PATH_SEPARATOR . $jobPath);
            spl_autoload_extensions('.php');
            spl_autoload_register(function ($class) {
                return spl_autoload($class);
            });
        }
    }


    /**
     * @param $data
     * @param $e
     */
    private function errorHandle($data, $e)
    {
        $data['runTimes'] += 1;
        $data['errMsg'] = $e->getMessage();
        if ($data['runTimes'] <= $data['maxTries']) {
            $this->rollback($data);
        } else {
            $this->taskArchive($data);
        }
        $traceStr = debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) . PHP_EOL;
        if ($this->isDaemon) {
            $this->logging->error($traceStr);
        } else {
            echo $traceStr;
        }
    }

    /**
     * 获取队列中的任务
     * @return mixed|void
     */
    private function getJob()
    {
        $result = $this->redis->rPop($this->list);
        if ($result) {
            $this->logging->info($result);
            $return = json_decode($result, true);
            $tips = '[' . $return['name'] . '] delivered at ' . $return['deliveryTime'] . PHP_EOL;
            if ($this->isDaemon) {
                $this->logging->info($tips);
            } else {
                echo $tips;
            }
            return $return;
        } else {
            return;
        }
    }

    /**
     * 失败任务回到队列等待重新执行
     * @param array $data
     */
    private function rollback(array $data)
    {
        $data['lastRunTime'] = date('Y-m-d H:i:s');
        $data = json_encode($data);
        $this->redis->lPush($this->list, $data);
    }

    /**
     * 失败任务归档
     * @param array $data
     */
    private function taskArchive(array $data)
    {
        $data = json_encode($data);
        $dataKey = md5($data);
        $this->redis->hSet($this->failedList, $dataKey, $data);
    }
}

<?php

namespace RedisQueue;

use Exception;
use RedisQueue\Traits\RedisTrait;
use Throwable;

/**
 * Class Server
 * 队列任务处理类
 * @package RedisQueue
 */
class Server implements ServerInterface
{
    use RedisTrait;

    protected $backup = false;

    /**
     * Server constructor.
     * @param \Redis $redis redis 对象实例
     * @param null $jobPath 需要实现自动加载任务类时传参
     * @param string $jobExt
     */
    public function __construct(\Redis $redis, $jobPath = null, $jobExt = '.php')
    {
        // 设置redis
        $this->redis = $redis;

        // 对于未使用composer加载的情况实现任务类文件自动加载
        if ($jobPath && is_dir($jobPath) && $jobExt) {
            set_include_path(get_include_path() . PATH_SEPARATOR . $jobPath);
            spl_autoload_extensions('.php');
            spl_autoload_register(function ($class) {
                return spl_autoload($class);
            });
        }
    }

    /**
     * 处理队列
     * @return mixed|void
     */
    public function process()
    {
        try {
            $data = $this->getJob();
            if ($data) {
                $obj = unserialize($data['job']);
                $obj->handle();
                // 完成后删除任务
                if ($this->backup) {
                    $this->delete();
                }
            }
        } catch (Exception $e) {
            $this->errorHandle($data, $e);
        } catch (Throwable $e) {
            $this->errorHandle($data, $e);
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
        if (PHP_SAPI === 'cli') {
            echo debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), PHP_EOL;
        }
    }

    /**
     * 获取队列中的任务
     * @return mixed|void
     */
    private function getJob()
    {
        if ($this->backup) {
            $result = $this->delete();
        } else {
            $result = null;
        }
        $result = $result ?: $this->redis->rPop($this->list);
        if ($result) {
            if ($this->backup) {
                $this->backup($result);
            }
            return json_decode($result, true);
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

    /**
     * 设置备份任务防止程序意外退出
     */
    public function setBackup()
    {
        $this->backup = true;
    }

    /**
     * @param $dataStr
     */
    private function backup($dataStr)
    {
        $this->redis->lPush($this->processList, $dataStr);
    }

    /**
     * @return string
     *
     */
    private function delete()
    {
        return $this->redis->rPop($this->processList);
    }
}
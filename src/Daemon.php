<?php

namespace RedisQueue;

abstract class Daemon
{
    protected $queue;
    protected $command;
    protected $work;
    protected $workNums;
    protected $pid = 'run.pid'; //任务执行中的进程
//    protected $lockPid = 'lock.pid';
    protected $curPid = null; //当前进程号

    public function __construct()
    {
        if (PHP_SAPI != 'cli') {
            echo 'Sorry, only support cli model!', PHP_EOL;
            exit(0);
        }
        global $argv;
        $this->work = $argv[0];
        $this->command = $argv[1];
        $this->workNums = $argv[2] > 0 ? $argv[2] : 1;
    }

    abstract public function work();
    
    abstract protected function lockWork();

    protected function start($workNums = null)
    {
        $workNums = $workNums ?: $this->workNums;
        if (file_exists($this->pid)) {
            printf("%s already running\n", $this->argv[0]);
            exit(0);
        }
        echo "Starting..." . PHP_EOL;
        for ($i = 0; $i < $workNums; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('could not fork');
            } else if ($pid) {
                if ($i == $this->workNums) {
                    exit(0);
                }
            } else {
                $this->curPid = posix_getpid();
                file_put_contents($this->pid, posix_getpid() . PHP_EOL, FILE_APPEND);
                pcntl_signal(SIGHUP, [&$this, "lockWork"]);
                $this->work();
            }
            sleep(2);
        }
    }

    protected function stop()
    {
        if (file_exists($this->pid)) {
            echo 'Stopping...', PHP_EOL;
            $pids = file($this->pid);
            foreach ($pids as $pid) {
                posix_kill($pid, SIGTERM);
            }
            unlink($this->pid);
            echo 'Stopped', PHP_EOL;
            return count($pids);
        } else {
            printf("%s haven't running\n", $this->argv[0]);
        }
    }

    protected function restart()
    {
        $workNums = $this->stop();
        $this->start($workNums);
    }

    protected function status()
    {
        if (file_exists($this->pid)) {
            printf("%s is running.\n", $this->work);
        } else {
            printf("%s is not running.\n", $this->work);
        }
    }

    protected function usage()
    {
        printf("Usage: %s {start | stop | restart | status} {number}\nDefault 1 process\n", $this->argv[0]);
    }

    public function run()
    {
        $command = $this->command;
        switch ($command) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'status':
                $this->status();
                break;
            default:
                $this->usage();
                break;
        }
    }
}

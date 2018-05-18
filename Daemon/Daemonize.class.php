<?php

class Daemonize 
{
    /* config */
    const pidfile   = __CLASS__;
    const pid        = -1;
    const uid       = 33;
    const gid       = 33;
    const sleep     = 5;
    
    private         $sig_handler;

    public function __construct($uid, $gid, $class)
    {
        //$this->pidfile = '/var/run/phpDaemon/'.$class.'.pid';
        $this->uid = $uid;
        $this->gid = $gid;
        $this->class = $class;
        $this->sig_handler = new Signal();
    }

    public function handle_signal($handler, $signals_client, $signals_list)
    {
        foreach($signals_list as $key => $val)
        {
            if (is_callable(array($signals_client, $val.'_callback')))
                pcntl_signal(constant($val), array($handler, 'set'));
            else die("Signal Callback ".$val."_callback() must be defined\n");
        }
    }

    private function daemon($argv)
    {
        $obj = $this->class;

        $this->pidfile = $obj::getPidLockFilePath().$this->class.'.pid';
        if (file_exists($this->pidfile)) {
            echo "The file $this->pidfile exists.\n\n";
            $obj::CannotStart($argv, $this->pidfile, 'Instance already running with PID : '.  file_get_contents($this->pidfile));
            exit(1);
        }

        $this->pid = pcntl_fork();
        if ($this->pid == -1) {
             die('could not fork');
        } else if ($this->pid) {
             // we are the parent
            @mkdir(dirname($this->pidfile), 0755);
            @chown(dirname($this->pidfile), self::uid);
            @chgrp(dirname($this->pidfile), self::gid);
            if (@file_put_contents($this->pidfile, $this->pid) === FALSE)
            {
                echo "Cannot create PID Lock file ".$this->pidfile."\n";
                $obj::CannotStart($argv, $this->pidfile, "Cannot create PID Lock file ".$this->pidfile);
                posix_kill($this->pid, SIGTERM);
                pcntl_waitpid($this->pid, $status, WUNTRACED);
                exit(1);
            }
            @chown($this->pidfile, self::uid);
            @chgrp($this->pidfile, self::gid);
            // chmod($this->pidfile, 0666);
            //exit($this->pid);
            exit(0);
        } else {
            posix_setuid(self::uid);
            posix_setgid(self::gid);
            return(getmypid());
        }
    }

    private function foreground()
    {
        $this->class->main_loop();
    }

    private function start($argv)
    {
        $pid = $this->daemon($argv);
        if (($this->pid == 0) && (file_exists($this->pidfile)))
        {
            $obj = new $this->class;
            $obj->setDaemonizer($this);
            if ($obj->init($argv))
            {
                $this->handle_signal($this->sig_handler, $obj, $obj->get_signals_to_trap());
                $obj->main_loop();
                unset($obj);
            }
            $this->finalize();
        }
    }

    private function stop()
    {
        if (file_exists($this->pidfile))
        {
            $pid = file_get_contents($this->pidfile);
            posix_kill($pid, SIGTERM);
        }
    }

    private function reload()
    {
        $obj = $this->class;
        $pidfile = $obj::getPidLockFilePath().$this->class.'.pid';
        if (file_exists($pidfile)) {
            $pid = file_get_contents($pidfile);
            posix_kill($pid, SIGHUP);
            @unlink($pidfile);
        }
    }

    private function status()
    {
        if (file_exists($this->pidfile)) {
            $pid = file_get_contents($this->pidfile);
            system(sprintf("ps ax | grep %s | grep -v grep", $pid));
        }
    }
    
    private function help($proc)
    {
        printf("%s start | stop | restart | status | foreground | help \n", $proc);
    }
    
    public function main($argv)
    {
        if(count($argv) < 2){
            $this->help($argv[0]);
            printf("please input help parameter\n");
            exit();
        }
        if($argv[1] === 'stop'){
            $this->stop();
        }else if($argv[1] === 'start'){
            $this->start($argv);
        }else if($argv[1] === 'restart'){
            $this->reload();
            $this->start($argv);
        }else if($argv[1] === 'status'){
            $this->status();
        }else{
            $this->help($argv[0]);
        }
    }
    
    public function finalize()
    {
        if ($this->pid == 0)
        {
            @unlink($this->pidfile);
        }
    }
}

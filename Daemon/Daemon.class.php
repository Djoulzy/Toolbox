<?php

abstract class Daemon
{
    private $_running_ = true;
    private $_traped_signals_ = array();
    
    protected $daemonizer;
    
    //static public $pidlockfilePath = '/var/run/phpDaemon/';
   
    public function __construct() {
        //self::$signal == null;
    }
    
    public function setDaemonizer($obj)
    {
        $this->daemonizer = $obj;
    }

    private function HubSignal()
    {
        $signo = Signal::get();
        if ($signo)
        {
            $methodname = $this->_traped_signals_[$signo].'_callback';
            $this->$methodname();
            switch($signo)
            {
                case SIGINT:
                case SIGTERM:
                        $this->_running_ = false;
                    break;
                default:;
            }
        }
        Signal::reset();
    }
    
    public function get_signals_to_trap()
    {
        $tmp = $this->trap_signals();
        foreach($tmp as $signames)
            $this->_traped_signals_[constant($signames)] = $signames;
        return $this->_traped_signals_;
    }

    public function main_loop()
    {
        echo "Daemon starts with PID : ".getmygid()."\n";
        while($this->_running_)
        {
            pcntl_signal_dispatch();
            $this->run();
            $this->HubSignal();
        }
    }
    
    abstract public function init($argv);
    abstract public function trap_signals();
    abstract public function run();
    abstract public static function getPidLockFilePath();
    abstract public static function CannotStart($argv, $pidfile, $str);
}

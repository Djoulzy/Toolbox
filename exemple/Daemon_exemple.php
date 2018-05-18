#!/usr/bin/php

<?php

require '../phar/Daemon-1.0.0.phar';

class Exemple extends Daemon
{
    private $mypid;

    public function __construct()
    {
        $this->mypid = getmypid();
        echo "Starting process with PID ".$this->mypid."\n";
    }

    public function init($argv)
    {

    }

    public static function CannotStart($argv, $pidfile)
    {
        echo 'Add params '.$argv[2].' for PID '.  file_get_contents($pidfile)."\n";
    }
    
    public function trap_signals()
    {
        return array('SIGHUP', 'SIGTERM', 'SIGUSR1');
    }
    
    public function SIGHUP_callback()
    {
        
    }
    
    public function SIGTERM_callback()
    {
        echo "Stopping process...\n";
    }
    
    public function SIGUSR1_callback()
    {
        echo "I'm Alive...\n";
    }
    
    public function run()
    {
        echo '.';
        sleep(2);
    }
    
    public function __destruct()
    {

    }
}

$daemon = new Daemonize(33,33, 'Exemple');
$daemon->main($argv);
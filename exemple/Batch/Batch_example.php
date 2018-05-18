#!/usr/bin/php
<?php

require '../../phar/Batch-1.0.0.phar';

class Step1 extends Batch
{
    private $toto_log;

    public function __construct()
    {
        $options_mandatory = array(
            'test' => 'test d\'option avec param'
        );
        
        $options_optinal = array(
            'zouzou' => 'test d\'option individuelle'
        );

        $options = parent::__construct($options_mandatory, null, $options_optinal);

        if (!isset($options['t']))
            die("probleme");
        
        $this->toto_log = $this->enableLogger('toto');
        $this->log($this->toto_log, 'OK');
    }
    
    public function countContents()
    {
        $cmd = 'cat '.$this->src_filename.' | wc -l';
        @exec($cmd, $output, $res_ok);

        if ($res_ok === 0)
        {
            return $output[0];
        }
        else
            die("Can't open file\n\n");
    }
    
    public function getNext()
    {
        return fgets($this->getSrcFilenameDescriptor());
    }
    
    public function process($data)
    {
        //return self::FAIL;
        sleep(1);
        return self::SUCCESS;
    }
    
    public function finalize()
    {
        die("Terminated\n");
    }
}

class Step2 extends Batch
{
    private $toto_log;

    public function __construct($var)
    {
        $this->quiet_mode = true;
        
        $options = parent::__construct();
        
        $this->toto_log = $this->enableLogger('toto');
        $this->log($this->toto_log, 'OK');
    }
    
    public function countContents()
    {
        $cmd = 'cat '.$this->src_filename.' | wc -l';
        @exec($cmd, $output, $res_ok);

        if ($res_ok === 0)
        {
            return $output[0];
        }
        else
            die("Can't open file\n\n");
    }
    
    public function getNext()
    {
        return fgets($this->getSrcFilenameDescriptor());
    }
    
    public function process($data)
    {
        //return self::FAIL;
        sleep(1);
        return self::SUCCESS;
    }
    
    public function finalize()
    {
        echo "Terminated\n";
    }
}

class Step3 extends Step1
{
    public function __construct($var)
    {
        $this->quiet_mode = true;
        $options = parent::__construct();
    }

    public function process($data)
    {
        //return self::FAIL;
        sleep(1);
        return self::SUCCESS;
    }
}

$import = new Step1();
$import->run();

$import = new Step2("test");
$import->run();

$import = new Step3("test");
$import->run();
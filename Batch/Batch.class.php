<?php

abstract class Batch
{
    const SIMULATE = 8;
    const CHOWN = 16;
    
    const SUCCESS = true;
    const FAIL = false;

    public $simulation = false;
    public $src_filename;
    public $connect_string;
    public $quiet_mode = false;
    
    private $db;
    private $processUser;
    private $src_path;
    private $_batch_src_file_descriptor;
    private $full_filename;
    private $_running_ = true;
    private $pidfile;
    private $query_source;
    private $db_res = null;
    private $bdd_offset = 0;

    private $_batch_total_lines = 0;
    private $actual_line = 1;
    private $_batch_ok = 0;
    private $_batch_fail = 0; 
    
    private $progressbar;
    private $sig_handler;

    /////////////////// Setters ///////////////////

    public function set_CONNECT_STRING($connect_string)
    {
        $this->connect_string = $connect_string;
        if (!($this->db = pg_connect($connect_string)))
        {
            die(pg_last_error($this->db));
            return false;
        }
        else return true;
    }
    
    public function set_FILENAME($filename)
    {
        $this->src_filename = basename($filename);
        $this->full_filename = $this->src_path.'/'.$this->src_filename;
    }
    
    public function setDatasource($source)
    {
        if (strtoupper($this->source) == 'BDD')
        {
            $this->query_source = $source;
            $this->db_res = $this->QueryDB($this->query_source, true);
        }
        else
            $this->db_res = fopen($source, 'r');
    }

    public function set_default($var_name, $value)
    {
        $this->$var_name = $value;
    }

    /////////////////// Getters ///////////////////
    
    public function getSrcFilenameDescriptor()
    {
        return $this->_batch_src_file_descriptor;
    }

    //////////////////// Tools ////////////////////
    
    public static function ID2Path($id)
    {
        return preg_replace('/(\d)/', '$1/', $id);
    }

    public static function Path2ID($path)
    {
        return str_replace('/', '', $path);
    }
    
    ///////////////////////////////////////////////
    
    private function makePIDfile()
    {
        $this->pidfile = '/var/run/batch/'.get_class($this).'.pid';
        if (file_exists($this->pidfile))
            die("One Batch is already running, stop it or wait for its termination...\n");
        else {
            @mkdir(dirname($this->pidfile), 0755);
            file_put_contents($this->pidfile, getmypid());
        }
    }
    
    public function __construct($mandatory = null, $optional = null, $individual = null)
    {
//        $this->makePIDfile();

        if (PHP_SAPI == "cli")
        {
            $options_mandatory = array(
                'param' => 'INI file with params'
            );
            if (is_array($mandatory)) $options_mandatory = array_merge($options_mandatory, $mandatory);
            
            $options_individual = array(
                'simulate' => 'No file copy or BDD insert',
                'help' => 'This message',
            );
            if (is_array($individual)) $options_individual = array_merge($options_individual, $individual);

            $options = checkOptions($options_mandatory, $optional, $options_individual);
            if (isset($options["s"])) $this->simulation = true;
            if (!empty($options['p'])) $this->setINIfile($options['p']);
        }
        else
        {
            $this->progressbar = new ProgressBar();
        }
        
        $this->sig_handler = new Signal();
        pcntl_signal(SIGTERM, array($this->sig_handler, 'set'));
        pcntl_signal(SIGINT, array($this->sig_handler, 'set'));
        
        $tmp = posix_getpwuid(posix_geteuid());
        $this->processUser = $tmp['name'];
        $this->src_path = getcwd();
        
        return $options;
    }

    public function enableLogger($name)
    {
        $id = $name.'_'.date('H-i-s_Ymd').'.log';
        return $id;
    }

    public function log($id, $str)
    {
        file_put_contents($id, date('Y/m/d H:i:s|').$str."\n", FILE_APPEND);
    }
    
    public function setINIfile($file)
    {
        $ini_conf = parse_ini_file($file, true);
        foreach($ini_conf['import'] as $categories_name => $values)
        {
            $funct = 'set_'.strtoupper($categories_name);
            if (method_exists ($this, $funct))
                $this->$funct($values);
            else
                $this->set_default(strtolower($categories_name), $values);
        }
    }

    public function displayParams()
    {
        _echo("\n==> Check params before start <==\n", "green");
        if ($this->processUser == 'root')
            _echo("!! WARNING you're running this script as root !!\n", "white", "red");
        else
            { _echo('PROCESS OWNER: ', "yellow"); _echo($this->processUser."\n"); }
        if (strtoupper($this->source) == 'FILE')
            { _echo('SRC FILENAME: ', "yellow"); _echo($this->src_filename."\n"); }
        else
            { _echo('SRC BDD : ', "yellow"); _echo($this->connect_string."\n"); }
        _echo('BDD INFOS: ', "yellow"); _echo($this->connect_string."\n");
        _echo('SIMULATION: ', "yellow"); _echo((($this->simulation)?'On':'Off')."\n");
        _echo('LINES TO PROCESS: ', "yellow"); _echo($this->_batch_total_lines."\n");
        _echo('MY OWN PID: ', "yellow"); _echo(getmypid()."\n");
    }
    
    public function displayProgressBar($str)
    {
        if (PHP_SAPI == "cli")
            displayProgressBar($this->_batch_total_lines, $this->actual_line++, $str, 100);
        else
            $this->progressbar->update($this->actual_line++, $this->_batch_total_lines);
    }
    
    public function QueryDB($query, $use_offset = false)
    {
        if (!empty($this->bdd_query_step) && $use_offset)
        {
            if ($this->bdd_offset > $this->_batch_total_lines) return false;
            $query .= ' OFFSET '.$this->bdd_offset.' LIMIT '.$this->bdd_query_step;
            $this->bdd_offset += $this->bdd_query_step;
        }
        if ($res = pg_query($this->db, $query))
        {
            return $res;
        }
        else die('BDD Error');
    }

    private function init_countContents()
    {
        if (strtoupper($this->source) == 'FILE') $this->_batch_src_file_descriptor = fopen($this->src_filename, 'r');
        $this->_batch_total_lines = $this->countContents();
    }
    
    private function HubSignal()
    {
        $signo = Signal::get();
        if ($signo)
        {
            switch($signo)
            {
                case SIGINT:
                case SIGTERM:
                        $this->finalize();
                        $this->_running_ = false;
                    break;
                default:;
            }
        }
        Signal::reset();
    }

    private function _getNext()
    {
        $data = $this->getNext($this->db_res);
        if (strtoupper($this->source) == 'BDD')
        {
            if (!$data && $this->bdd_offset < $this->_batch_total_lines)
            {
                pg_free_result($this->db_res);
                $this->db_res = $this->QueryDB($this->query_source, true);
                $data = $this->getNext($this->db_res);
            }
        }
        return $data;
    }
    
    public function run()
    {
        $this->init_countContents();

        if (!$this->quiet_mode)
        {
            if (PHP_SAPI == "cli") $this->displayParams();
            _echo("\n");
            askConfirm();
        }

        pcntl_signal_dispatch();
        $this->HubSignal();
        _echo("Running batch ".  get_class($this)."\n");
        
        while (($tmp = $this->_getNext()) &&  ($this->_running_ == true))
        {
            if ($this->process($tmp) === self::SUCCESS)
                $this->_batch_ok++;
            else
                $this->_batch_fail++;
            $this->displayProgressBar('Ok:'.$this->_batch_ok.' / Fail:'.$this->_batch_fail);
            
            pcntl_signal_dispatch();
            $this->HubSignal();
        }
        _echo("\n\nGame Over...\n\n");
        @unlink($this->pidfile);
    }
    
    public function __destruct()
    {
        @unlink($this->pidfile);
    }
    
    abstract public function countContents();
    abstract public function getNext($data);
    abstract public function process($data);
    abstract public function finalize();
}
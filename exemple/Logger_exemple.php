<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../phar/Logger-1.0.0.phar';

//define('_LOG_VERBOSITY_LEVEL_', 1);

class Exemple
{
    public function __construct()
    {
        logger::singleton('Toolbox', 'Logger', logger::USE_COLORS);
        logger::append(logger::INFO, 'Exemple', '__construct', 'Start logging process');
    }
 
    public function run()
    {
        logger::append(logger::DEBUG, 'Exemple', 'run', 'running...');
        
        logger::setSessionVar1('OK');
        logger::append(logger::INFO, 'Exemple', 'run', 'Storing session var 1');
        
        logger::setSessionVar2(getmypid());
        logger::append(logger::WARNING, 'Exemple', 'run', 'Storing session var 2');
        
        logger::append(logger::FAIL, 'Exemple', 'run', 'still running...');
        logger::append(logger::ERROR, 'Exemple', 'run', 'still running...');
    }
    
    public function __destruct()
    {
        logger::append(logger::INFO, 'Exemple', '__destruct', 'Close logging process');
    }
}

$test = new Exemple;
$test->run();
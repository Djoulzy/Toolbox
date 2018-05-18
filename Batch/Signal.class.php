<?php

final class Signal
{   
    public static $signo = 0;
    
    public static function set($signo)
    {
        self::$signo = $signo;
    }

    public static function get()
    {
        return(self::$signo);
    }

    public static function reset()
    {
        self::$signo = 0;
    }
}
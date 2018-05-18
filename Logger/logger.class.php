<?php

class logger
{
    const TEST = 6;
    const DEBUG = 5;
    const INFO = 4;
    const FAIL = 3;
    const WARNING = 2;
    const ERROR = 1;
    
    const USE_COLORS = true;
    const NO_COLOR = false;
    
	private static $_instance = null;
    private static $_EXEC_PID_ = '--';
    private static $_USER_ID_ = '--';
    private static $_CONTENT_ID_ = '--';
    private static $_FRONT_CODE_ = '---';
    private static $vip_user = false;
    private static $colorObj = false;

    private static $labels = array();
    
    private static $padding = array(
        '_SEVERITY_' => '%s',
        '_EXEC_PID_' => '%s',
        '_USER_ID_' => '%8s',
        '_CONTENT_ID_' => '%10s',
        '_FRONT_CODE_' => '%3s',
        '_SCRIPTNAME_' => '%s',
        '_METHOD_' => '%s',
        '_MESSAGE_' => '%s',
    );

    private static $colors = array(
        6 => 'light_green',
        5 => 'light_gray',
        4 => 'white',
        3 => 'light_purple',
        2 => 'light_red',
        1 => 'red',
        '_SEVERITY_' => null,
        '_EXEC_PID_' => null,
        '_USER_ID_' => 'light_blue',
        '_CONTENT_ID_' => 'yellow',
        '_FRONT_CODE_' => 'green',
        '_SCRIPTNAME_' => 'purple',
        '_METHOD_' => 'dark_gray',
        '_MESSAGE_' => '%s'
    );
    
    private function __construct($application, $module = null, $colorize = false)
    {
//        self::$labels = array(
//            self::TEST => '['.self::TEST.']TEST',
//            self::DEBUG => '['.self::DEBUG.']DEBG',
//            self::INFO => '['.self::INFO.']INFO',
//            self::FAIL => '['.self::FAIL.']FAIL',
//            self::WARNING => '['.self::WARNING.']WARN',
//            self::ERROR => '['.self::ERROR.']ERRR'
//        );

        self::$labels = array(
            self::TEST => 'TEST',
            self::DEBUG => 'DEBG',
            self::INFO => 'INFO',
            self::FAIL => 'FAIL',
            self::WARNING => 'WARN',
            self::ERROR => 'ERRR'
        );

        if ($colorize) self::$colorObj = new Colors();
        openlog($application, LOG_ODELAY|LOG_PID, LOG_LOCAL7);
//        if (PHP_SAPI == "cli")
//            self::$_EXEC_PID_ = strtoupper(substr(md5((string)(microtime()).'localhost'), 0, 6));
//        else
//            self::$_EXEC_PID_ = strtoupper(substr(md5((string)(microtime()).$_SERVER['REMOTE_ADDR']), 0, 6));
        
        if (($module == null) && defined('_EXPECTED_API_VERSION_')) define('_LOG_NAMED_API_', _EXPECTED_API_VERSION_);
        else define('_LOG_NAMED_API_', $module);
    }

	public static function singleton($application, $module, $colorize = false)
	{
		if (is_null(self::$_instance)) self::$_instance = new self($application, $module, $colorize);
		return self::$_instance;
	}
    
    public static function setUser($id)
    {
        self::$_USER_ID_ = $id;
        if (defined('_VIP_USERS_LOG_'))
            if (in_array($id, explode(',', _VIP_USERS_LOG_))) self::$vip_user = true;
    }
    
    public static function setSessionVar1($id)
    {
        self::setUser($id);
    }
    
    public static function setContent($id)
    {
        self::$_CONTENT_ID_ = $id;
    }

    public static function setSessionVar2($id)
    {
        self::setContent($id);
    }
    
    public static function setFrontCode($id)
    {
        self::$_FRONT_CODE_ = $id;
    }

    public static function setSessionVar3($id)
    {
        self::setFrontCode($id);
    }
    
    private static function format($field, $val, $color)
    {
        $str = sprintf(self::$padding[$field], $val);
        if (self::$colorObj) $str = self::$colorObj->getColoredString($str, $color, null);
        return $str;
    }
    
    public static function append($severity, $scriptname, $method, $message)
    {
        if (self::$vip_user) $level = 6;
        elseif (defined('_LOG_VERBOSITY_LEVEL_')) $level = _LOG_VERBOSITY_LEVEL_;
        else $level = 2;
        
        if ($severity <= $level)
        {
            if (!isset(self::$labels[$severity])) $severity = self::INFO;
            
            $log_str = _LOG_NAMED_API_.
                    '|'.self::format('_SEVERITY_', self::$labels[$severity], self::$colors[$severity]).
//                    '|'.self::format('_EXEC_PID_', self::$_EXEC_PID_, self::$colors['_EXEC_PID_']).
                    '|'.self::format('_USER_ID_', self::$_USER_ID_, self::$colors['_USER_ID_']).
                    '|'.self::format('_CONTENT_ID_', self::$_CONTENT_ID_, self::$colors['_CONTENT_ID_']).
                    '|'.self::format('_FRONT_CODE_', self::$_FRONT_CODE_, self::$colors['_FRONT_CODE_']).
                    '|'.self::format('_SCRIPTNAME_', $scriptname, self::$colors['_SCRIPTNAME_']).
                    '|'.self::format('_METHOD_', $method, self::$colors['_METHOD_']).
                    '|'.self::format('_MESSAGE_', $message, self::$colors[$severity]);

            syslog(LOG_INFO, $log_str);
//            file_put_contents('/tmp/test', $log_str."\n", FILE_APPEND);
        }
    }
    
    function __destruct() {
        closelog();
    }
}
<?php
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
set_time_limit(0);
@ob_implicit_flush(1);

function _echo($str, $fg = null, $bg = null, $return_value = false)
{
    if ((isset($fg) || isset($bg)) && (PHP_SAPI == "cli"))
    {
        $colors = new Colors();
        $str = $colors->getColoredString($str, $fg, $bg);
    }
    if ($return_value) return $str;
    else echo $str;
}

function MC_LOG($file, $message)
{
    file_put_contents($file, date('Y/m/d H:i:s|').$message."\n", FILE_APPEND);
}

function checkOptions($mandatory, $optional, $individual)
{
    global $argv;
    
    $short = $long = $doc = '';
    if (is_array($mandatory))
        foreach($mandatory as $val => $desc)
            { $short .= $val[0].':'; $long[] = $val.':'; $doc .= "\t-".$val[0].', --'.$val.' <xxx> : '.$desc."\n"; }
    if (is_array($optional))
        foreach($optional as $val => $desc) 
            { $short .= $val[0].'::'; $long[] = $val.'::'; $doc .= "\t-".$val[0].', --'.$val.' [xxx] : '.$desc."\n"; }
    if (is_array($individual))
        foreach($individual as $val => $desc)
            { $short .= $val[0]; $long[] = $val; $doc .= "\t-".$val[0].', --'.$val.' : '.$desc."\n"; }
    
    $bad_opt = false;
    $options = getopt($short, $long);
    
    if (!empty($mandatory) && empty($options))
        $options['h'] = true;

    if (isset($options['h']) || isset($options['help']))
    {
        _echo("\nUsage: ".$argv[0]." <options>\n", "yellow");
        _echo($doc, "yellow");
        _echo("\n");
        die();
    }

    return $options;
}

function askConfirm()
{
    if (PHP_SAPI == "cli")
    {
        _echo("Are you sure you want to do this?  Type 'y' to continue: ", "red");
        $handle = fopen ('php://stdin', 'r');
        $line = fgets($handle);
        if(trim($line) != 'y'){
            _echo("\n\nABORTING!\n\n", "red");
            die();;
        }
        _echo("\n");
        _echo("Thank you, continuing...\n\n", "green");
    }
}

function displayProgressBar($total_length, $actual, $message, $barsize = 50)
{
    $percent = ceil(($actual/$total_length)*100);
    $progress = round($barsize*($percent/100));

    $bar = str_pad('', $progress, '#');
    $compt = str_pad('', $barsize, '=');

    $deb = 'Progress : '.str_pad($actual, strlen($total_length), ' ', STR_PAD_LEFT).'/'.$total_length." ||";
    $fin = '|| '.$percent.'% - '.$message;

    _echo($deb.$compt.$fin);
    if (PHP_SAPI == "cli") _echo("\r");
    _echo($deb);
    _echo($bar.">", "red");
    if (PHP_SAPI == "cli") _echo("\r");
}
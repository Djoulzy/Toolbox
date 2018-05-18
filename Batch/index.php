<?php

if (PHP_SAPI == "cli")
{
    if (!class_exists('Colors'))
        require 'Colors.class.php';
}
else
{
    require 'ProgressBar.class.php';
}

require 'cli_tools.func.php';
require 'Signal.class.php';
require 'Batch.class.php';
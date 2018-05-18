#!/usr/bin/php
<?php
$project = $argv[1];
/*
 * PHP Phar - How to create and use a Phar archive
 */

$ini_conf = parse_ini_file($project.'/manifest.ini', true);
$phar_name = $project.'-'.$ini_conf['Manifest']['version'].'.phar';

$p = new Phar('phar/'.$phar_name, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, $phar_name);
//issue the Phar::startBuffering() method call to buffer changes made to the archive until you issue the Phar::stopBuffering() command
$p->startBuffering();

//set the Phar file stub
//the file stub is merely a small segment of code that gets run initially when the Phar file is loaded, 
//and it always ends with a __HALT_COMPILER()


//Adding files to the archive
$p['text.txt'] = $ini_conf['Manifest']['text'];
//Adding files to an archive using Phar::buildFromDirectory()
//adds all of the PHP files in the stated directory to the Phar archive
$p->buildFromDirectory($project.'/', '$(.*)\.php$');

//Stop buffering write requests to the Phar archive, and save changes to disk
$p->stopBuffering();
echo $phar_name." archive has been saved\n";

?>
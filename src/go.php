<?php
// File example: src/script.php

// update this to the path to the "vendor/"
// directory, relative to this file

require_once __DIR__.'/Yacwl.class.php';
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

$crawler = new Crawler();
$crawler->addContent('<html><body><p>Hello World!</p></body></html>');


$stop;
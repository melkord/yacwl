<?php
// File example: src/script.php

// update this to the path to the "vendor/"
// directory, relative to this file



require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/Yacwl.class.php';



use Yacwl\Yacwl;
use Symfony\Component\DomCrawler\Crawler;


$yacwl = new Yacwl( new Crawler());

$yacwl->execute();
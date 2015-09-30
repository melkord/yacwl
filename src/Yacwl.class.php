<?php
/**
 * Created by PhpStorm.
 * User: melkord
 * Date: 9/30/15
 * Time: 10:29 PM
 */

namespace Yacwl;


class Yacwl
{
    protected $finder;

    public function __construct($finder)
    {
        $this->adapter = $finder;
    }
}
<?php
/*
 *  This file is a part of small-env
 *  Copyright 2023 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Registry\Trait;

trait RegistryTrait
{

    protected static self|null $instance = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance == null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

}
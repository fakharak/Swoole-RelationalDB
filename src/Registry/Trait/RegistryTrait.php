<?php

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
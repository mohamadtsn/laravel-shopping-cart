<?php

namespace Tests\Helpers;

class Container
{
    public static Container $instance;

    protected array $instances = [];

    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function make($abstract, $concrete = null)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->instances[$abstract] = $concrete;
        return $this;
    }

    public function resolve($abstract)
    {
        return $this->instances[$abstract] ?? null;
    }
}
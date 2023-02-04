<?php

use Tests\Helpers\Container;

function app($abstract = null)
{
    if (is_null($abstract)) {
        return Container::getInstance();
    }
    return Container::getInstance()->resolve($abstract);
}
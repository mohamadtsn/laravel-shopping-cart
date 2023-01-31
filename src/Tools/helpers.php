<?php
if (!function_exists('normalizePrice')) {
    function normalizePrice($price): float
    {
        return is_string($price) ? (float)$price : $price;
    }
}

if (!function_exists('isMultiArray')) {
    function isMultiArray(array $array): bool
    {
        foreach ($array as $v) {
            if (is_array($v)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('issetAndHasValueOrAssignDefault')) {
    function issetAndHasValueOrAssignDefault(&$var, $default = false)
    {
        if ((isset($var)) && ($var !== '')) {
            return $var;
        }

        return $default;
    }
}

if (!function_exists('formatValue')) {
    function formatValue($value, $format_numbers, $config)
    {
        if ($format_numbers && $config['format_numbers']) {
            return number_format($value, $config['decimals'], $config['dec_point'], $config['thousands_sep']);
        }

        return $value;
    }
}
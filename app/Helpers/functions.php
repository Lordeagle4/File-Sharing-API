<?php

use App\Core\Config;

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return Config::getInstance()->get($key, $default);
    }
}
if (!function_exists('config_set')) {
    function config_set(string $key, $value): void
    {
        Config::getInstance()->set($key, $value);
    }
}
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return Config::getInstance()->get('env.' . $key, $default);
    }
}
function base_path(string $path = ''): string
{
    return __DIR__ . '/../../' . ltrim($path, '/');
}
if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $var) {
            echo "<pre>";
            print_r($var);
            echo "</pre>";
        }
        exit;
    }
}

if (!function_exists('dump')) {
    function dump(...$vars)
    {
        foreach ($vars as $var) {
            echo "<pre>";
            print_r($var);
            echo "</pre>";
        }
    }
}

<?php

namespace App\Core;

use Dotenv\Dotenv;

class DotenvLoader
{
    public static function load(string $path): void
    {
        $dotenv = Dotenv::createImmutable(dirname($path));
        $dotenv->safeLoad();
    }
}

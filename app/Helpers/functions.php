<?php

function base_path(string $path = ''): string
{
    return __DIR__ . '/../../' . ltrim($path, '/');
}
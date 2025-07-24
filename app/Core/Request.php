<?php

namespace App\Core;

class Request
{
    public static function input(string $key, mixed $default = null): mixed
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        return $data[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

public static function require(array $fields, string $source = 'input'): array
{
    $missing = [];
    $data = match ($source) {
        'post' => $_POST,
        'get' => $_GET,
        'file' => $_FILES,
        default => json_decode(file_get_contents('php://input'), true) ?? [],
    };

    $values = [];

    foreach ($fields as $field) {
        if (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== '0')) {
            $missing[] = $field;
        } else {
            $values[$field] = $data[$field];
        }
    }

    if (!empty($missing)) {
        Response::json(['error' => 'Missing required fields', 'fields' => $missing], 422);
        exit;
    }

    return $values;
}

}

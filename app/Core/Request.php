<?php

namespace App\Core;

class Request
{
    public static function input(string $key, mixed $default = null): mixed
    {
        // Priority: $_POST > $_GET > JSON body
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        // Fallback to JSON body
        $data = json_decode(file_get_contents('php://input'), true);
        if (is_array($data) && array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function require(array $fields, string $source = 'input'): array
    {
        $missing = [];

        $data = match ($source) {
            'post' => $_POST,
            'get' => $_GET,
            'file' => $_FILES,
            default => array_merge($_POST, $_GET, json_decode(file_get_contents('php://input'), true) ?? []),
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

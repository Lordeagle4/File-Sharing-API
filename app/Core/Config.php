<?php

namespace App\Core;

use App\Core\DotenvLoader as EnvLoader;

class Config
{
    protected static ?self $instance = null;
    protected array $config = [];

    private function __construct()
    {
        $this->config = require base_path('app/config.php');

        $envPath = base_path('.env');
        $envVars = EnvLoader::load($envPath);

        $this->config['env'] = $envVars;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $ref = &$this->config;

        foreach ($keys as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        $ref = $value;
    }
}

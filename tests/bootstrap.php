<?php

if (!class_exists('rcube_plugin', false)) {
    class rcube_plugin
    {
        public function init(): void
        {
        }
    }
}

if (!class_exists('roundcube_oidc_test_config', false)) {
    class roundcube_oidc_test_config
    {
        /** @var array<string, mixed> */
        private $values;

        /** @param array<string, mixed> $values */
        public function __construct(array $values)
        {
            $this->values = $values;
        }

        public function get(string $key, $default = null)
        {
            return $this->values[$key] ?? $default;
        }
    }
}

if (!class_exists('rcmail', false)) {
    class rcmail
    {
        /** @var self */
        private static $instance;

        /** @var roundcube_oidc_test_config */
        public $config;

        /** @param array<string, mixed> $config */
        public static function configure(array $config): void
        {
            self::$instance = new self();
            self::$instance->config = new roundcube_oidc_test_config($config);
        }

        public static function get_instance(): self
        {
            return self::$instance;
        }
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../roundcube_oidc.php';

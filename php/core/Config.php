<?php

namespace Core;

class Config
{
    private $settings;
    private static $_instance;

    /**
     * @param string $file The file that contains the settings.
     */
    public function __construct(string $file)
    {
        $this->settings = require $file;
    }

    /**
     * Intanciate the Only One Config Class.
     *
     * @param string $file The file that contains the settings.
     *
     * @return Config
     */
    public static function getInstance(string $file): Config
    {
        if (is_null(self::$_instance))
            self::$_instance = new Config($file);

        return self::$_instance;
    }

    public function get(string $key): mixed
    {
        if (!isset($this->settings[$key]))
            return null;

        return $this->settings[$key];
    }
}

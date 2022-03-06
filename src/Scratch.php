<?php

declare(strict_types=1);

namespace Scratch;

use Illuminate\Support\Str;

class Scratch
{
    /** @var array */
    public static $config;

    public static function title(): string
    {
        return self::config()['title'];
    }

    private static function config(): array
    {
        if (static::$config) {
            return static::$config;
        }

        static::$config = require getcwd() . '/scratch.php';

        return static::$config;
    }

    public static function outputFileName(): string
    {
        return Str::slug(self::config()['title']);
    }

    public static function author(): string
    {
        return self::config()['author'];
    }
}

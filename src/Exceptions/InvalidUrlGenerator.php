<?php

namespace Spatie\MediaLibrary\Exceptions;

use Exception;

class InvalidUrlGenerator extends Exception
{
    public static function doesntExist($class)
    {
        return new static("Class {$class} doesn't exist");
    }

    public static function isntAUrlGenerator($class)
    {
        return new static("Class {$class} must implement `Spatie\\MediaLibrary\\UrlGenerator\\UrlGenerator`");
    }
}

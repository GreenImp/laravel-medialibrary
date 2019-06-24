<?php

namespace Spatie\MediaLibrary\Exceptions;

use Exception;

class InvalidPathGenerator extends Exception
{
    public static function doesntExist($class)
    {
        return new static("Class {$class} doesn't exist");
    }

    public static function isntAPathGenerator($class)
    {
        return new static("Class {$class} must implement `Spatie\\MediaLibrary\\PathGenerator\\PathGenerator`");
    }
}

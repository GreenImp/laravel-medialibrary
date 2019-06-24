<?php

namespace Spatie\MediaLibrary\Exceptions;

use Exception;

class InvalidConversion extends Exception
{
    public static function unknownName($name)
    {
        return new static("There is no conversion named `{$name}`");
    }
}

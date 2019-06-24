<?php

namespace Spatie\MediaLibrary\Helpers;

use Config;
use Spatie\TemporaryDirectory\TemporaryDirectory as BaseTemporaryDirectory;

class TemporaryDirectory
{
    public static function create()
    {
        return new BaseTemporaryDirectory(static::getTemporaryDirectoryPath());
    }

    protected static function getTemporaryDirectoryPath()
    {
        $path = Config::get('medialibrary.temporary_directory_path', storage_path('medialibrary/temp'));

        return $path.DIRECTORY_SEPARATOR.str_random(32);
    }
}

<?php

namespace Spatie\MediaLibrary\Exceptions;

use Exception;

class UrlCannotBeDetermined extends Exception
{
    public static function mediaNotPubliclyAvailable($storagePath, $publicPath)
    {
        return new static("Storage path `{$storagePath}` is not part of public path `{$publicPath}`");
    }

    public static function filesystemDoesNotSupportTemporaryUrls()
    {
        return new static('Generating temporary URLs only works on the S3 filesystem driver');
    }
}

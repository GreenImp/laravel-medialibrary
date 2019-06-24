<?php

namespace Spatie\MediaLibrary\FileAdder;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Input;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\RequestDoesNotHaveFile;

class FileAdderFactory
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $subject
     * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return \Spatie\MediaLibrary\FileAdder\FileAdder
     */
    public static function create(Model $subject, $file)
    {
        return app(FileAdder::class)
            ->setSubject($subject)
            ->setFile($file);
    }

    public static function createFromRequest(Model $subject, $key)
    {
        return static::createMultipleFromRequest($subject, [$key])->first();
    }

    public static function createMultipleFromRequest(Model $subject, array $keys = [])
    {
        return with(new Collection($keys))
            ->map(function ($key) use ($subject) {
                if (! Input::hasFile($key)) {
                    throw RequestDoesNotHaveFile::create($key);
                }

                $files = Input::file($key);

                if (! is_array($files)) {
                    return static::create($subject, $files);
                }

                return array_map(function ($file) use ($subject) {
                    return static::create($subject, $file);
                }, $files);
            })
            ->flatten();
    }

    public static function createAllFromRequest(Model $subject)
    {
        $fileKeys = array_keys(Input::file());

        return static::createMultipleFromRequest($subject, $fileKeys);
    }
}

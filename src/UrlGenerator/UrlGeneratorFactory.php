<?php

namespace Spatie\MediaLibrary\UrlGenerator;

use Config;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\Exceptions\InvalidUrlGenerator;
use Spatie\MediaLibrary\Conversion\ConversionCollection;
use Spatie\MediaLibrary\PathGenerator\PathGeneratorFactory;

class UrlGeneratorFactory
{
    public static function createForMedia(Media $media, $conversionName = '')
    {
        $urlGeneratorClass = Config::get('medialibrary.url_generator')
            ?: 'Spatie\MediaLibrary\UrlGenerator\\'.ucfirst($media->getDiskDriverName()).'UrlGenerator';

        static::guardAgainstInvalidUrlGenerator($urlGeneratorClass);

        $urlGenerator = app($urlGeneratorClass);
        $pathGenerator = PathGeneratorFactory::create();

        $urlGenerator
            ->setMedia($media)
            ->setPathGenerator($pathGenerator);

        if ($conversionName !== '') {
            $conversion = ConversionCollection::createForMedia($media)->getByName($conversionName);

            $urlGenerator->setConversion($conversion);
        }

        return $urlGenerator;
    }

    public static function guardAgainstInvalidUrlGenerator($urlGeneratorClass)
    {
        if (! class_exists($urlGeneratorClass)) {
            throw InvalidUrlGenerator::doesntExist($urlGeneratorClass);
        }

        if (! is_subclass_of($urlGeneratorClass, UrlGenerator::class)) {
            throw InvalidUrlGenerator::isntAUrlGenerator($urlGeneratorClass);
        }
    }
}

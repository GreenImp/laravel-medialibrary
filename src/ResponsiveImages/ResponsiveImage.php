<?php

namespace Spatie\MediaLibrary\ResponsiveImages;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\Filesystem\Filesystem;
use Spatie\MediaLibrary\UrlGenerator\UrlGeneratorFactory;
use Spatie\MediaLibrary\PathGenerator\PathGeneratorFactory;

class ResponsiveImage
{
    /** @var string */
    public $fileName = '';

    /** @var \Spatie\MediaLibrary\Models\Media */
    public $media;

    public static function register(Media $media, $fileName, $conversionName)
    {
        $responsiveImages = $media->responsive_images;

        $responsiveImages[$conversionName]['urls'][] = $fileName;

        $media->responsive_images = $responsiveImages;

        $media->save();
    }

    public static function registerTinySvg(Media $media, $base64Svg, $conversionName)
    {
        $responsiveImages = $media->responsive_images;

        $responsiveImages[$conversionName]['base64svg'] = $base64Svg;

        $media->responsive_images = $responsiveImages;

        $media->save();
    }

    public function __construct($fileName, Media $media)
    {
        $this->fileName = $fileName;

        $this->media = $media;
    }

    public function url()
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this->media);

        return $urlGenerator->getResponsiveImagesDirectoryUrl().$this->fileName;
    }

    public function generatedFor()
    {
        $propertyParts = $this->getPropertyParts();

        array_pop($propertyParts);

        array_pop($propertyParts);

        return implode('_', $propertyParts);
    }

    public function width()
    {
        $propertyParts = $this->getPropertyParts();

        array_pop($propertyParts);

        return (int) last($propertyParts);
    }

    public function height()
    {
        $propertyParts = $this->getPropertyParts();

        return (int) last($propertyParts);
    }

    protected function getPropertyParts()
    {
        $propertyString = $this->stringBetween($this->fileName, '___', '.');

        return explode('_', $propertyString);
    }

    protected function stringBetween($subject, $startCharacter, $endCharacter)
    {
        $between = strstr($subject, $startCharacter);

        $between = str_replace('___', '', $between);

        $between = strstr($between, $endCharacter, true);

        return $between;
    }

    public function delete()
    {
        $pathGenerator = PathGeneratorFactory::create();

        $path = $pathGenerator->getPathForResponsiveImages($this->media);

        $fullPath = $path.$this->fileName;

        app(Filesystem::class)->removeFile($this->media, $fullPath);

        $responsiveImages = $this->media->responsive_images;

        unset($responsiveImages[$this->generatedFor()]);

        $this->media->responsive_images = $responsiveImages;

        $this->media->save();

        return $this;
    }
}

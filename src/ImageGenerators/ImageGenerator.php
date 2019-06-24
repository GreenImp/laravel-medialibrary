<?php

namespace Spatie\MediaLibrary\ImageGenerators;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\Conversion\Conversion;

interface ImageGenerator
{
    public function canConvert(Media $media);

    /**
     * Receive a file and return a thumbnail in jpg/png format.
     *
     * @param string $path
     * @param \Spatie\MediaLibrary\Conversion\Conversion|null $conversion
     *
     * @return string
     */
    public function convert($path, Conversion $conversion = null);

    public function canHandleMime($mime = '');

    public function canHandleExtension($extension = '');

    public function getType();
}

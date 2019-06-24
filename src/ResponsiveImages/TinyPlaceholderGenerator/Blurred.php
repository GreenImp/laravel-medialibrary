<?php

namespace Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator;

use Spatie\MediaLibrary\Helpers\ImageFactory;

class Blurred implements TinyPlaceholderGenerator
{
    public function generateTinyPlaceholder($sourceImagePath, $tinyImageDestinationPath)
    {
        $sourceImage = ImageFactory::load($sourceImagePath);

        $sourceImage->width(32)->blur(5)->save($tinyImageDestinationPath);
    }
}

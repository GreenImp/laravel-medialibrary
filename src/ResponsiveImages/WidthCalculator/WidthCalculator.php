<?php

namespace Spatie\MediaLibrary\ResponsiveImages\WidthCalculator;

use Illuminate\Support\Collection;

interface WidthCalculator
{
    public function calculateWidthsFromFile($imagePath);

    public function calculateWidths(int $filesize, int $width, int $height);
}

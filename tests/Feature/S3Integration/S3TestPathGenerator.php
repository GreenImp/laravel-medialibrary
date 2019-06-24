<?php

namespace Spatie\MediaLibrary\Tests\Feature\S3Integration;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\PathGenerator\PathGenerator;

class S3TestPathGenerator implements PathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media)
    {
        return $this->getBasePath($media).'/';
    }

    /*
     * Get the path for conversions of the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media)
    {
        return $this->getBasePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media)
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    /*
     * Get a (unique) base path for the given media.
     */
    protected function getBasePath(Media $media)
    {
        return (S3IntegrationTest::getS3BaseTestDirectory()).'/'.$media->getKey();
    }
}

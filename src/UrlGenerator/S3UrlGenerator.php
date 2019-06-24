<?php

namespace Spatie\MediaLibrary\UrlGenerator;

use Config;
use DateTimeInterface;
use Spatie\MediaLibrary\Filesystem\FilesystemManager;

class S3UrlGenerator extends BaseUrlGenerator
{
    /** @var \Illuminate\Filesystem\FilesystemManager */
    protected $filesystemManager;

    public function __construct(FilesystemManager $filesystemManager)
    {
        $this->filesystemManager = $filesystemManager;

        parent::__construct();
    }

    /**
     * Get the url for a media item.
     *
     * @return string
     */
    public function getUrl()
    {
        $url = $this->getPathRelativeToRoot();

        if ($root = Config::get('medialibrary.disks.'.$this->media->disk.'.root')) {
            $url = $root.'/'.$url;
        }

        $url = $this->rawUrlEncodeFilename($url);

        return Config::get('medialibrary.s3.domain').'/'.$url;
    }

    /**
     * Get the temporary url for a media item.
     *
     * @param \DateTimeInterface $expiration
     * @param array $options
     *
     * @return string
     */
    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = [])
    {
        return $this
            ->filesystemManager
            ->disk($this->media->disk)
            ->temporaryUrl($this->getPath(), $expiration, $options);
    }

    /**
     * Get the url for the profile of a media item.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getPathRelativeToRoot();
    }

    /**
     * Get the url to the directory containing responsive images.
     *
     * @return string
     */
    public function getResponsiveImagesDirectoryUrl()
    {
        return Config::get('medialibrary.s3.domain').'/'.$this->pathGenerator->getPathForResponsiveImages($this->media);
    }
}

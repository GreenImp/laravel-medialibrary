<?php

namespace Spatie\MediaLibrary\UrlGenerator;

use Config;
use DateTimeInterface;
use Spatie\MediaLibrary\Exceptions\UrlCannotBeDetermined;

class LocalUrlGenerator extends BaseUrlGenerator
{
    /**
     * Get the url for a media item.
     *
     * @return string
     *
     * @throws \Spatie\MediaLibrary\Exceptions\UrlCannotBeDetermined
     */
    public function getUrl()
    {
        $url = $this->getBaseMediaDirectoryUrl().'/'.$this->getPathRelativeToRoot();

        $url = $this->makeCompatibleForNonUnixHosts($url);

        $url = $this->rawUrlEncodeFilename($url);

        return $url;
    }

    /**
     * @param \DateTimeInterface $expiration
     * @param array              $options
     *
     * @return string
     *
     * @throws \Spatie\MediaLibrary\Exceptions\UrlCannotBeDetermined
     */
    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = [])
    {
        throw UrlCannotBeDetermined::filesystemDoesNotSupportTemporaryUrls();
    }

    /*
     * Get the path for the profile of a media item.
     */
    public function getPath()
    {
        return $this->getStoragePath().'/'.$this->getPathRelativeToRoot();
    }

    protected function getBaseMediaDirectoryUrl()
    {
        if ($diskUrl = Config::get("medialibrary.disks.{$this->media->disk}.url")) {
            return str_replace(url('/'), '', $diskUrl);
        }

        if (! starts_with($this->getStoragePath(), public_path())) {
            throw UrlCannotBeDetermined::mediaNotPubliclyAvailable($this->getStoragePath(), public_path());
        }

        return $this->getBaseMediaDirectory();
    }

    /*
     * Get the directory where all files of the media item are stored.
     */
    protected function getBaseMediaDirectory()
    {
        return str_replace(public_path(), '', $this->getStoragePath());
    }

    /*
     * Get the path where the whole medialibrary is stored.
     */
    protected function getStoragePath()
    {
        $diskRootPath = Config::get("medialibrary.disks.{$this->media->disk}.root");

        return realpath($diskRootPath);
    }

    protected function makeCompatibleForNonUnixHosts($url)
    {
        if (DIRECTORY_SEPARATOR != '/') {
            $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
        }

        return $url;
    }

    /**
     * Get the url to the directory containing responsive images.
     *
     * @return string
     */
    public function getResponsiveImagesDirectoryUrl()
    {
        return url($this->getBaseMediaDirectoryUrl().'/'.$this->pathGenerator->getPathForResponsiveImages($this->media)).'/';
    }
}

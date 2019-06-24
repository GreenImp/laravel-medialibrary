<?php

namespace Spatie\MediaLibrary;

use Spatie\MediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Spatie\MediaLibrary\Filesystem\Filesystem;

class MediaObserver
{
    public function creating(Media $media)
    {
        if ($media->shouldSortWhenCreating()) {
            $media->setHighestOrderNumber();
        }
    }

    public function updating(Media $media)
    {
        if ($media->file_name !== $media->getOriginal('file_name')) {
            app(Filesystem::class)->syncFileNames($media);
        }
    }

    public function updated(Media $media)
    {
        if (is_null($media->getOriginal('model_id'))) {
            return;
        }

        if ($media->manipulations !== json_decode($media->getOriginal('manipulations'), true)) {
            $eventDispatcher = Media::getEventDispatcher();
            Media::unsetEventDispatcher();

            app(FileManipulator::class)->createDerivedFiles($media);

            Media::setEventDispatcher($eventDispatcher);
        }
    }

    public function deleted(Media $media)
    {
        if (in_array(SoftDeletingTrait::class, class_uses_recursive(get_class($media)))) {
            if (! $media->forceDeleting) {
                return;
            }
        }

        app(Filesystem::class)->removeAllFiles($media);
    }
}

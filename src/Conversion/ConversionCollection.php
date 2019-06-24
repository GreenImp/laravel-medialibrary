<?php

namespace Spatie\MediaLibrary\Conversion;

use Illuminate\Support\Arr;
use Spatie\Image\Manipulations;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\Exceptions\InvalidConversion;

class ConversionCollection extends Collection
{
    /** @var \Spatie\MediaLibrary\Models\Media */
    protected $media;

    /**
     * @param \Spatie\MediaLibrary\Models\Media $media
     *
     * @return static
     */
    public static function createForMedia(Media $media)
    {
        return (new static())->setMedia($media);
    }

    /**
     * @param \Spatie\MediaLibrary\Models\Media $media
     *
     * @return $this
     */
    public function setMedia(Media $media)
    {
        $this->media = $media;

        $this->items = [];

        $this->addConversionsFromRelatedModel($media);

        $this->addManipulationsFromDb($media);

        return $this;
    }

    /**
     *  Get a conversion by it's name.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws \Spatie\MediaLibrary\Exceptions\InvalidConversion
     */
    public function getByName($name)
    {
        $conversion = $this->first(function (Conversion $conversion) use ($name) {
            return $conversion->getName() === $name;
        });

        if (! $conversion) {
            throw InvalidConversion::unknownName($name);
        }

        return $conversion;
    }

    /**
     * Add the conversion that are defined on the related model of
     * the given media.
     *
     * @param \Spatie\MediaLibrary\Models\Media $media
     */
    protected function addConversionsFromRelatedModel(Media $media)
    {
        $modelName = $media->model_type;

        /** @var \Spatie\MediaLibrary\HasMedia\HasMedia $model */
        $model = new $modelName();

        /*
         * In some cases the user might want to get the actual model
         * instance so conversion parameters can depend on model
         * properties. This will causes extra queries.
         */
        if ($model->registerMediaConversionsUsingModelInstance) {
            $model = $media->model;

            $model->mediaConversion = [];
        }

        $model->registerAllMediaConversions($media);

        $this->items = $model->mediaConversions;
    }

    /**
     * Add the extra manipulations that are defined on the given media.
     *
     * @param \Spatie\MediaLibrary\Models\Media $media
     */
    protected function addManipulationsFromDb(Media $media)
    {
        with(new Collection($media->manipulations))->each(function ($manipulations, $conversionName) {
            $this->addManipulationToConversion(new Manipulations([$manipulations]), $conversionName);
        });
    }

    public function getConversions($collectionName = '')
    {
        if ($collectionName === '') {
            return $this;
        }

        return $this->filter(function($value, $key) use ($collectionName){
            return $value->shouldBePerformedOn($collectionName);
        });
    }

    /*
     * Get all the conversions in the collection that should be queued.
     */
    public function getQueuedConversions($collectionName = '')
    {
        return $this->getConversions($collectionName)->filter(function($value, $key){
            return $value->shouldBeQueued();
        });
    }

    /*
     * Add the given manipulation to the conversion with the given name.
     */
    protected function addManipulationToConversion(Manipulations $manipulations, $conversionName)
    {
        optional($this->first(function (Conversion $conversion) use ($conversionName) {
            return $conversion->getName() === $conversionName;
        }))->addAsFirstManipulations($manipulations);

        if ($conversionName === '*') {
            $this->each(function($item, $key) use ($manipulations){
                $item->addAsFirstManipulations(clone $manipulations);
            });
        }
    }

    /*
     * Get all the conversions in the collection that should not be queued.
     */
    public function getNonQueuedConversions($collectionName = '')
    {
        return $this->getConversions($collectionName)->reject(function($value, $key){
            return $value->shouldBeQueued();
        });
    }

    /*
     * Return the list of conversion files.
     */
    public function getConversionsFiles($collectionName = '')
    {
        $fileName = pathinfo($this->media->file_name, PATHINFO_FILENAME);

        return $this->getConversions($collectionName)->map(function (Conversion $conversion) use ($fileName) {
            return $conversion->getConversionFile($fileName);
        });
    }
}

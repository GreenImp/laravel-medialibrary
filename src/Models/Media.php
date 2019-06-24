<?php

namespace Spatie\MediaLibrary\Models;

use Config;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Helpers\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Illuminate\Contracts\Support\Responsable;
use Spatie\MediaLibrary\Conversion\Conversion;
use Spatie\MediaLibrary\Filesystem\Filesystem;
use Spatie\MediaLibrary\Models\Concerns\IsSorted;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\Helpers\TemporaryDirectory;
use Spatie\MediaLibrary\Conversion\ConversionCollection;
use Spatie\MediaLibrary\ImageGenerators\FileTypes\Image;
use Spatie\MediaLibrary\UrlGenerator\UrlGeneratorFactory;
use Spatie\MediaLibrary\Models\Traits\CustomMediaProperties;
use Spatie\MediaLibrary\ResponsiveImages\RegisteredResponsiveImages;

use Illuminate\Support\Facades\Response;

class Media extends Model
{
    use IsSorted,
        CustomMediaProperties;

    const TYPE_OTHER = 'other';

    protected $guarded = [];

    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'responsive_images' => 'array',
    ];

    protected $table = 'media';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->mutateToArrays();
    }

    public static function boot() {
        parent::boot();

        self::saving(function(Media $model) {
            // before we save we need to cast the array properties to JSON
            $model->mutateToJson();
        });

        self::saved(function(Media $model) {
            // after we've saved, let's mutate the json back to arrays
            $model->mutateToArrays();
        });
    }

    /**
     * Mutates the array properties from JSON to Arrays
     */
    public function mutateToArrays()
    {
        foreach($this->casts as $attribute => $type){
            if($type === 'array') {
                $this->attributes[$attribute] = isset($this->attributes[$attribute]) ? json_decode($this->attributes[$attribute]) : [];
            }
        }
    }

    /**
     * Mutates the array properties to JSON
     */
    public function mutateToJson()
    {
        foreach($this->casts as $attribute => $type){
            if(($type === 'array') && !is_string($this->{$attribute})) {
                $this->attributes[$attribute] = json_encode($this->{$attribute});
            }
        }
    }

    public function model()
    {
        return $this->morphTo();
    }

    /*
     * Get the full url to a original media file.
    */
    public function getFullUrl($conversionName = '')
    {
        return url($this->getUrl($conversionName));
    }

    /*
     * Get the url to a original media file.
     */
    public function getUrl($conversionName = '')
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getUrl();
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, $conversionName = '', array $options = [])
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getTemporaryUrl($expiration, $options);
    }

    /*
     * Get the path to the original media file.
     */
    public function getPath($conversionName = '')
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getPath();
    }

    public function getImageGenerators()
    {
        return new Collection(Config::get('medialibrary.image_generators'));
    }

    public function getTypeAttribute()
    {
        $type = $this->getTypeFromExtension();

        if ($type !== self::TYPE_OTHER) {
            return $type;
        }

        return $this->getTypeFromMime();
    }

    public function getTypeFromExtension()
    {
        /** @var ImageGenerator $imageGenerator */
        $imageGenerator = $this->getImageGenerators()
            ->map(function ($className) {
                return app($className);
            })
            ->first(function($key, $generator){
                /** @var $generator ImageGenerator */
                return $generator->canHandleExtension(strtolower($this->extension));
            });

        return $imageGenerator
            ? $imageGenerator->getType()
            : static::TYPE_OTHER;
    }

    public function getTypeFromMime()
    {
        /** @var ImageGenerator $imageGenerator */
        $imageGenerator = $this->getImageGenerators()
            ->map(function ($className) {
                return app($className);
            })
            ->first(function($key, $generator) {
                /** @var ImageGenerator $generator */
                return $generator->canHandleMime($this->mime_type);
            });

        return $imageGenerator
            ? $imageGenerator->getType()
            : static::TYPE_OTHER;
    }

    public function getExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getHumanReadableSizeAttribute()
    {
        return File::getHumanReadableSize($this->size);
    }

    public function getDiskDriverName()
    {
        return strtolower(Config::get("medialibrary.disks.{$this->disk}.driver"));
    }

    /*
     * Determine if the media item has a custom property with the given name.
     */
    public function hasCustomProperty($propertyName)
    {
        return array_has($this->custom_properties, $propertyName);
    }

    /**
     * Get the value of custom property with the given name.
     *
     * @param string $propertyName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCustomProperty($propertyName, $default = null)
    {
        return array_get($this->custom_properties, $propertyName, $default);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomProperty($name, $value)
    {
        $customProperties = $this->custom_properties;

        array_set($customProperties, $name, $value);

        $this->custom_properties = $customProperties;

        return $this;
    }

    public function forgetCustomProperty($name)
    {
        $customProperties = $this->custom_properties;

        array_forget($customProperties, $name);

        $this->custom_properties = $customProperties;

        return $this;
    }

    /*
     * Get all the names of the registered media conversions.
     */
    public function getMediaConversionNames()
    {
        $conversions = ConversionCollection::createForMedia($this);

        return $conversions->map(function (Conversion $conversion) {
            return $conversion->getName();
        })->toArray();
    }

    public function hasGeneratedConversion($conversionName)
    {
        $generatedConversions = $this->getGeneratedConversions();

        return !empty($generatedConversions[$conversionName]) ? $generatedConversions[$conversionName] : false;
    }

    public function markAsConversionGenerated($conversionName, $generated)
    {
        $this->setCustomProperty("generated_conversions.{$conversionName}", $generated);

        $this->save();

        return $this;
    }

    public function getGeneratedConversions()
    {
        return new Collection($this->getCustomProperty('generated_conversions', []));
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        $downloadHeaders = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type' => $this->mime_type,
            'Content-Length' => $this->size,
            'Content-Disposition' => 'attachment; filename="'.$this->file_name.'"',
            'Pragma' => 'public',
        ];

        return Response::stream(function () {
            $stream = $this->stream();

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $downloadHeaders);
    }

    public function getResponsiveImageUrls($conversionName = '')
    {
        return $this->responsiveImages($conversionName)->getUrls();
    }

    public function hasResponsiveImages($conversionName = '')
    {
        return count($this->getResponsiveImageUrls($conversionName)) > 0;
    }

    public function getSrcset($conversionName = '')
    {
        return $this->responsiveImages($conversionName)->getSrcset();
    }

    public function toHtml()
    {
        return $this->img();
    }

    /**
     * @param string|array $conversion
     * @param array $extraAttributes
     *
     * @return string
     */
    public function img($conversion = '', array $extraAttributes = [])
    {
        if (! (new Image())->canHandleMime($this->mime_type)) {
            return '';
        }

        if (is_array($conversion)) {
            $attributes = $conversion;

            $conversion = $attributes['conversion'] ?: '';

            unset($attributes['conversion']);

            $extraAttributes = array_merge($attributes, $extraAttributes);
        }

        $attributeString = with(new Collection($extraAttributes))
            ->map(function ($value, $name) {
                return $name.'="'.$value.'"';
            })->implode(' ');

        if (strlen($attributeString)) {
            $attributeString = ' '.$attributeString;
        }

        $media = $this;

        $viewName = 'image';

        $width = '';

        if ($this->hasResponsiveImages($conversion)) {
            $viewName = Config::get('medialibrary.responsive_images.use_tiny_placeholders')
                ? 'responsiveImageWithPlaceholder'
                : 'responsiveImage';

            $width = $this->responsiveImages($conversion)->files->first()->width();
        }

        return \View::make("medialibrary.{$viewName}", compact(
            'media',
            'conversion',
            'attributeString',
            'width'
        ));
    }

    public function move(HasMedia $model, $collectionName = 'default')
    {
        $newMedia = $this->copy($model, $collectionName);

        $this->delete();

        return $newMedia;
    }

    public function copy(HasMedia $model, $collectionName = 'default')
    {
        throw new \Exception('Functionality not complete for Laravel 4.2');
        /*$temporaryDirectory = TemporaryDirectory::create();

        $temporaryFile = $temporaryDirectory->path($this->file_name);

        app(Filesystem::class)->copyFromMediaLibrary($this, $temporaryFile);

        $newMedia = $model
            ->addMedia($temporaryFile)
            ->usingName($this->name)
            ->withCustomProperties($this->custom_properties)
            ->toMediaCollection($collectionName);

        $temporaryDirectory->delete();

        return $newMedia;*/
    }

    public function responsiveImages($conversionName = '')
    {
        return new RegisteredResponsiveImages($this, $conversionName);
    }

    public function stream()
    {
        /** @var Filesystem $filesystem */
        $filesystem = app(Filesystem::class);

        return $filesystem->getStream($this);
    }

    public function __invoke(...$arguments)
    {
        return $this->img(...$arguments);
    }
}

<?php

namespace Spatie\MediaLibrary;

use Config;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\Commands\CleanCommand;
use Spatie\MediaLibrary\Commands\ClearCommand;
use Spatie\MediaLibrary\Filesystem\Filesystem;
use Spatie\MediaLibrary\Commands\RegenerateCommand;
use Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\WidthCalculator;
use Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\TinyPlaceholderGenerator;

class MediaLibraryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->package('spatie/medialibrary', null, __DIR__.'/../');

        /*if (! class_exists('CreateMediaTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_media_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_media_table.php'),
            ], 'migrations');
        }*/

        $mediaClass = Config::get('medialibrary.media_model');

        $mediaClass::observe(new MediaObserver());
    }

    public function register()
    {
        $this->app->singleton(MediaRepository::class, function () {
            $mediaClass = Config::get('medialibrary.media_model');

            return new MediaRepository(new $mediaClass);
        });

//        $this->app->bind('command.medialibrary:regenerate', RegenerateCommand::class);
//        $this->app->bind('command.medialibrary:clear', ClearCommand::class);
//        $this->app->bind('command.medialibrary:clean', CleanCommand::class);

        $this->app->bind(Filesystem::class, Filesystem::class);

//        $this->app->bind(WidthCalculator::class, Config::get('medialibrary.responsive_images.width_calculator'));
//        $this->app->bind(TinyPlaceholderGenerator::class, Config::get('medialibrary.responsive_images.tiny_placeholder_generator'));
//
//        $this->commands([
//            'command.medialibrary:regenerate',
//            'command.medialibrary:clear',
//            'command.medialibrary:clean',
//        ]);

        $this->registerDeprecatedConfig();
    }

    protected function registerDeprecatedConfig()
    {
        if (! Config::get('medialibrary.disk_name')) {
            Config::set(['medialibrary.disk_name' => Config::get('medialibrary.default_filesystem')]);
        }
    }

    /**
     * Register the package's component namespaces.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @param  string  $path
     * @return void
     */
    public function package($package, $namespace = null, $path = null)
    {
        $namespace = $this->getPackageNamespace($package, $namespace);

        // In this method we will register the configuration package for the package
        // so that the configuration options cleanly cascade into the application
        // folder to make the developers lives much easier in maintaining them.
        $path = $path ?: $this->guessPackagePath();

        $config = $path.'/config';

        if ($this->app['files']->isDirectory($config))
        {
            $this->app['config']->package($package, $config, $namespace);
        }

        // Next we will check for any "language" components. If language files exist
        // we will register them with this given package's namespace so that they
        // may be accessed using the translation facilities of the application.
        $lang = $path.'/lang';

        if ($this->app['files']->isDirectory($lang))
        {
            $this->app['translator']->addNamespace($namespace, $lang);
        }

        // Next, we will see if the application view folder contains a folder for the
        // package and namespace. If it does, we'll give that folder precedence on
        // the loader list for the views so the package views can be overridden.
        $appView = $this->getAppViewPath($package);

        if ($this->app['files']->isDirectory($appView))
        {
            $this->app['view']->addNamespace($namespace, $appView);
        }

        // Finally we will register the view namespace so that we can access each of
        // the views available in this package. We use a standard convention when
        // registering the paths to every package's views and other components.
        $view = $path.'/resources/views';

        if ($this->app['files']->isDirectory($view))
        {
            $this->app['view']->addNamespace($namespace, $view);
        }
    }
}

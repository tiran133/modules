<?php namespace Pingpong\Modules;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Class Module
 * @package Pingpong\Modules
 */
class Module extends ServiceProvider {

    /**
     * The laravel application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The module name.
     *
     * @var
     */
    protected $name;


    /**
     * The constructor.
     *
     * @param Application $app
     * @param $name
     * @param array $attributes
     */
    public function __construct (Application $app, $name, array $attributes,$path)
    {

        $this->app = $app;
        $this->name = $name;
        $this->attributes = new Collection($attributes);
        $this->setAttribute('path',realpath($path));

        //var_dump($this->getAttribute('path'));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName ()
    {

        return $this->name;
    }

    /**
     * Get name in lower case.
     *
     * @return string
     */
    public function getLowerName ()
    {

        return strtolower($this->name);
    }

    /**
     * Get name in studly case.
     *
     * @return string
     */
    public function getStudlyName ()
    {

        return Str::studly($this->name);
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription ()
    {

        return $this->getAttribute('description');
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias ()
    {

        return $this->getAttribute('alias');
    }

    /**
     * Get priority.
     *
     * @return string
     */
    public function getPriority ()
    {

        return $this->getAttribute('priority');
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath ()
    {

        return $this->getAttribute('path');
    }

    /**
     * Set path.
     *
     * @param string $path
     * @return $this
     */
    public function setPath ($path)
    {

        $this->setAttribute('path',$path);

        return $this;
    }


    /**
     * Get all of the configuration files for the module.
     *
     * @return array
     */
    private function getConfigurationFiles ($path)
    {

        $files = [];

        foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
            $files[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return $files;
    }


    /**
     * Register the modules's component namespaces.
     *
     * @param  string $package
     * @param  string $namespace
     * @param  string $path
     * @return void
     */
    public function package ($package, $namespace = null, $path = null)
    {

        //$namespace = $this->getPackageNamespace($package, $namespace);

        // In this method we will register the configuration package for the package
        // so that the configuration options cleanly cascade into the application
        // folder to make the developers lives much easier in maintaining them.
        // $path = $path ?: $this->guessPackagePath();

        $generatorPaths = $this->app['config']->get('modules.paths.generator');

        $config = $path . '/' . $generatorPaths['config'];

        foreach ($this->getConfigurationFiles($config) as $configname => $configfile) {
            $this->mergeConfigFrom($configfile, $package . '.' . $configname);
        }


        // Next we will check for any "language" components. If language files exist
        // we will register them with this given package's namespace so that they
        // may be accessed using the translation facilities of the application.
        $lang = $path . '/' . $generatorPaths['lang'];

        if ($this->app['files']->isDirectory($lang)) {
            $this->app['translator']->addNamespace($namespace, $lang);
        }

        // Next, we will see if the application view folder contains a folder for the
        // package and namespace. If it does, we'll give that folder precedence on
        // the loader list for the views so the package views can be overridden.
//        $appView = $this->getAppViewPath($package);
//
//        if ($this->app['files']->isDirectory($appView))
//        {
//            $this->app['view']->addNamespace($namespace, $appView);
//        }

        // Finally we will register the view namespace so that we can access each of
        // the views available in this package. We use a standard convention when
        // registering the paths to every package's views and other components.
        $view = $path . '/' . $generatorPaths['views'];

        if ($this->app['files']->isDirectory($view)) {
            $this->app['view']->addNamespace($namespace, $view);

        }
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot ()
    {

        $this->package('modules.config.' . $this->getLowerName(), $this->getLowerName(), $this->path);

        $this->fireEvent('boot');
    }


    /**
     * Register the module.
     *
     * @return void
     */
    public function register ()
    {

        $this->registerProviders();

        $this->registerFiles();

        $this->fireEvent('register');
    }

    /**
     * Register the module event.
     *
     * @param string $event
     */
    protected function fireEvent ($event)
    {

        $this->app['events']->fire(sprintf('modules.%s.' . $event, $this->getLowerName()), [$this]);
    }

    /**
     * Register the service providers from this module.
     *
     * @return void
     */
    protected function registerProviders ()
    {

        foreach ($this->getAttribute('providers', []) as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Register the files from this module.
     *
     * @return void
     */
    protected function registerFiles ()
    {

        foreach ($this->getAttribute('files', []) as $file) {
            include $this->path . '/' . $file;
        }
    }

    /**
     * Handle call __toString.
     *
     * @return string
     */
    public function __toString ()
    {

        return $this->getStudlyName();
    }

    /**
     * Determine whether the given status same with the current module status.
     *
     * @param $status
     * @return bool
     */
    public function isStatus ($status)
    {

        return $this->getAttribute('active', 0) == $status;
    }

    /**
     * Determine whether the current module activated.
     *
     * @return bool
     */
    public function enabled ()
    {

        return $this->active();
    }

    /**
     * Alternate for "enabled" method.
     *
     * @return bool
     */
    public function active ()
    {

        return $this->isStatus(1);
    }

    /**
     * Determine whether the current module not activated.
     *
     * @return bool
     */
    public function notActive ()
    {

        return ! $this->active();
    }

    /**
     * Alias for "notActive" method.
     *
     * @return bool
     */
    public function disabled ()
    {

        return ! $this->enabled();
    }

    /**
     * Set active state for current module.
     *
     * @param $active
     * @return bool
     */
    public function setActive ($active)
    {

        return $this->json()->set('active', $active)->save();
    }

    /**
     * Disable the current module.
     *
     * @return bool
     */
    public function disable ()
    {

        return $this->setActive(0);
    }

    /**
     * Enable the current module.
     *
     * @return bool
     */
    public function enable ()
    {

        return $this->setActive(1);
    }

    /**
     * Delete the current module.
     *
     * @return bool
     */
    public function delete ()
    {

        return $this->json()->getFilesystem()->deleteDirectory($this->getPath(), true);
    }

    /**
     * Get extra path.
     *
     * @param $path
     * @return string
     */
    public function getExtraPath ($path)
    {

        return $this->getPath() . '/' . $path;
    }

    /**
     * Handle call to __get method.
     *
     * @param $key
     * @return mixed
     */
    public function __get ($key)
    {

        return $this->getAttribute($key);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    private function getAttribute ($key,$default = null)
    {
        return $this->attributes->get($key, $default);
    }

    /**
     * @return Collection
     */
    public function getAttributes(){

        return $this->attributes;

    }

    /**
     * @param $key
     * @param $value
     */
    private function setAttribute ($key, $value)
    {
        $this->attributes->put($key, $value);
    }

}

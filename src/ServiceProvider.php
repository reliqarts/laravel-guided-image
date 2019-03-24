<?php

namespace ReliqArts\GuidedImage;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use ReliqArts\GuidedImage\Console\Commands\DumpImageCache;
use ReliqArts\GuidedImage\Helpers\Config;

/**
 *  Guided Image Service Provider.
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Assets location.
     */
    protected $assetsDir = __DIR__ . '/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected $commands = [
        DumpImageCache::class,
    ];

    /**
     * Perform post-registration booting of services.
     */
    public function boot(Router $router): void
    {
        // register routes
        $this->handleRoutes($router);
        // register config
        $this->handleConfig();
        // publish assets
        $this->handleAssets();
        // publish commands
        $this->handleCommands();
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        // bind guided contract to resolve to model
        $this->app->bind(
            'ReliqArts\GuidedImage\Contracts\Guided',
            'ReliqArts\GuidedImage\GuidedImage'
        );
    }

    /**
     * Publish assets.
     */
    protected function handleAssets(): void
    {
        $this->publishes([
            "{$this->assetsDir}/config/config.php" => config_path('guidedimage.php'),
        ], 'guided-image-config');

        $this->publishes([
            "{$this->assetsDir}/database/migrations/" => database_path('migrations'),
        ], 'guided-image-migrations');
    }

    /**
     * Register Configuraion.
     */
    protected function handleConfig(): void
    {
        // merge config
        $this->mergeConfigFrom("{$this->assetsDir}/config/config.php", 'guidedimage');
    }

    /**
     * Register routes.
     */
    protected function handleRoutes(Router $router): void
    {
        if (!$this->app->routesAreCached()) {
            // explicitly bind guided image model
            $this->bindRouteModel($router);
            // get the routes
            require_once "{$this->assetsDir}/routes/web.php";
        }
    }

    /**
     * Explicitly bind guided model instance to router, hence
     * overriding binded GuidedImage model (since they both implement the Guided contract).
     */
    private function bindRouteModel(Router $router): void
    {
        $routeModel = Config::getRouteModel();
        $routeModelNamespace = Config::getRouteModelNamespace();

        // get absolute guided model class
        $absGuidedModel = $routeModelNamespace . $routeModel;

        // explicitly bind guidedimage instance to router
        $router->model(strtolower($routeModel), $absGuidedModel);
    }

    /**
     * Command files.
     */
    private function handleCommands(): void
    {
        // Register the commands...
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }
}

<?php

namespace ReliQArts\GuidedImage;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use ReliQArts\GuidedImage\Helpers\RouteHelper;

/**
 *  GuidedImageServiceProvider.
 */
class GuidedImageServiceProvider extends ServiceProvider
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
    protected $assetsDir = __DIR__.'/..';

    /**
     * Explicitly bind guided model instance to router, hence
     * overriding binded GuidedImage model (since they both implement the Guided contract).
     */
    private function bindRouteModel(Router $router)
    {
        $routeModel = RouteHelper::getRouteModel();
        $routeModelNamespace = RouteHelper::getRouteModelNamespace();

        // get absolute guided model class
        $absGuidedModel = $routeModelNamespace.$routeModel;

        // explicitly bind guidedimage instance to router
        $router->model(strtolower($routeModel), $absGuidedModel);
    }

    /**
     * Publish assets.
     *
     * @return void
     */
    protected function publishAssets()
    {
        $this->publishes([
            "$this->assetsDir/config/config.php" => config_path('guidedimage.php'),
        ], 'config');

        $this->publishes([
            "$this->assetsDir/database/migrations/" => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * Register Configuraion.
     */
    protected function registerConfig()
    {
        // merge config
        $this->mergeConfigFrom("$this->assetsDir/config/config.php", 'guidedimage');
    }

    /**
     * Register routes.
     *
     * @return void
     */
    protected function registerRoutes(Router $router)
    {
        // explicitly bind guided image model
        $this->bindRouteModel($router);

        // get the routes
        require_once "$this->assetsDir/routes/web.php";
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        // register routes
        if (!$this->app->routesAreCached()) {
            $this->registerRoutes($router);
        }

        $this->publishAssets();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        // bind guided contract to resolve to model
        $this->app->bind(
            'ReliQArts\GuidedImage\Contracts\Guided',
            'ReliQArts\GuidedImage\GuidedImage'
        );
    }
}

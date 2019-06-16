<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Monolog\Handler\StreamHandler;
use ReliqArts\GuidedImage\Console\Commands\DumpImageCache;
use ReliqArts\GuidedImage\Contracts\ConfigProvider as ConfigProviderContract;
use ReliqArts\GuidedImage\Contracts\Guided;
use ReliqArts\GuidedImage\Contracts\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contracts\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contracts\Logger as LoggerContract;
use ReliqArts\GuidedImage\Services\ConfigProvider;
use ReliqArts\GuidedImage\Services\ImageDispenser;
use ReliqArts\GuidedImage\Services\ImageUploader;
use ReliqArts\GuidedImage\Services\Logger;
use ReliqArts\ServiceProvider as ReliqArtsServiceProvider;
use ReliqArts\Services\ConfigProvider as ReliqArtsConfigProvider;

/**
 *  Guided Image Service Provider.
 */
final class ServiceProvider extends ReliqArtsServiceProvider
{
    protected const CONFIG_KEY = 'guidedimage';
    protected const ASSET_DIRECTORY = __DIR__ . '/..';
    protected const LOGGER_NAME = self::CONFIG_KEY . '-logger';
    protected const LOG_FILENAME = self::CONFIG_KEY;

    /**
     * List of commands.
     *
     * @var array
     */
    protected $commands = [
        DumpImageCache::class,
    ];

    /**
     * @var ConfigProviderContract
     */
    private $configProvider;

    /**
     * Perform post-registration booting of services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        parent::boot();

        $this->handleRoutes();
        $this->handleCommands();
        $this->handleMigrations();
    }

    public function registerBindings(): void
    {
        $this->configProvider = new ConfigProvider(
            new ReliqArtsConfigProvider(
                resolve(ConfigRepository::class),
                $this->getConfigKey()
            )
        );
        $guidedModelFQCN = $this->configProvider->getGuidedModelNamespace()
            . $this->configProvider->getGuidedModelName();

        $this->app->singleton(
            ConfigProviderContract::class,
            function (): ConfigProviderContract {
                return $this->configProvider;
            }
        );

        $this->app->singleton(
            LoggerContract::class,
            function (): LoggerContract {
                $logger = new Logger($this->getLoggerName());
                $logFile = storage_path(sprintf('logs/%s.log', $this->getLogFilename()));
                $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

                return $logger;
            }
        );

        $this->app->singleton(
            ImageDispenserContract::class,
            ImageDispenser::class
        );

        $this->app->singleton(
            ImageUploaderContract::class,
            ImageUploader::class
        );

        $this->app->bind(Guided::class, $guidedModelFQCN);
    }

    public function provides()
    {
        return array_merge(
            $this->commands,
            [
                Guided::class,
            ]
        );
    }

    protected function handleConfig(): void
    {
        $this->mergeConfigFrom(sprintf('%s/config/config.php', $this->getAssetDirectory()), 'guidedimage');
    }

    /**
     * Command files.
     */
    private function handleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * @throws BindingResolutionException
     */
    private function handleRoutes(): void
    {
        $router = $this->app->make('router');
        $modelName = $this->configProvider->getGuidedModelName();

        if (!$this->app->routesAreCached()) {
            $router->model(strtolower($modelName), $this->configProvider->getGuidedModelNamespace() . $modelName);

            /** @noinspection PhpIncludeInspection */
            require_once sprintf('%s/routes/web.php', $this->getAssetDirectory());
        }
    }

    private function handleMigrations()
    {
        $this->loadMigrationsFrom(sprintf('%s/database/migrations', $this->getAssetDirectory()));
    }
}

<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Monolog\Handler\StreamHandler;
use ReliqArts\GuidedImage\Console\Command\ClearSkimDirectories;
use ReliqArts\GuidedImage\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Contract\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contract\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Contract\Logger as LoggerContract;
use ReliqArts\GuidedImage\Service\ConfigProvider;
use ReliqArts\GuidedImage\Service\ImageDispenser;
use ReliqArts\GuidedImage\Service\ImageUploader;
use ReliqArts\GuidedImage\Service\Logger;
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
        ClearSkimDirectories::class,
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

    public function register(): void
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

        $this->app->bind(GuidedImage::class, $guidedModelFQCN);
    }

    public function provides(): array
    {
        return array_merge(
            $this->commands,
            [
                GuidedImage::class,
            ]
        );
    }

    protected function handleConfig(): void
    {
        $configFile = sprintf('%s/config/config.php', $this->getAssetDirectory());
        $configKey = $this->getConfigKey();

        $this->mergeConfigFrom($configFile, $configKey);

        $this->publishes(
            [$configFile => config_path(sprintf('%s.php', $configKey))],
            sprintf('%s-config', $configKey)
        );
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

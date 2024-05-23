<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use InvalidArgumentException;
use ReliqArts\Contract\Logger as LoggerContract;
use ReliqArts\Factory\LoggerFactory;
use ReliqArts\GuidedImage\Console\Command\ClearSkimDirectories;
use ReliqArts\GuidedImage\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\GuidedImage\Contract\FileHelper as FileHelperContract;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Contract\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contract\ImageManager as ImageManagerContract;
use ReliqArts\GuidedImage\Contract\ImageUploader as ImageUploaderContract;
use ReliqArts\GuidedImage\Helper\FileHelper;
use ReliqArts\GuidedImage\Service\ConfigProvider;
use ReliqArts\GuidedImage\Service\ImageDispenser;
use ReliqArts\GuidedImage\Service\ImageManager;
use ReliqArts\GuidedImage\Service\ImageUploader;
use ReliqArts\Service\ConfigProvider as ReliqArtsConfigProvider;
use ReliqArts\ServiceProvider as ReliqArtsServiceProvider;

/**
 *  Guided Image Service Provider.
 */
final class ServiceProvider extends ReliqArtsServiceProvider
{
    protected const CONFIG_KEY = 'guidedimage';

    protected const ASSET_DIRECTORY = __DIR__.'/..';

    protected const LOGGER_NAME = self::CONFIG_KEY.'-logger';

    protected const LOG_FILE_BASENAME = self::CONFIG_KEY;

    /**
     * List of commands.
     */
    protected array $commands = [
        ClearSkimDirectories::class,
    ];

    private ConfigProviderContract $configProvider;

    /**
     * Perform post-registration booting of services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->handleRoutes();
        $this->handleConfig();
        $this->handleCommands();
        $this->handleMigrations();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function register(): void
    {
        $this->configProvider = new ConfigProvider(
            new ReliqArtsConfigProvider(
                resolve(ConfigRepository::class),
                $this->getConfigKey()
            )
        );

        $guidedModelFQCN = $this->configProvider->getGuidedModelNamespace()
            .$this->configProvider->getGuidedModelName();

        $this->app->bind(GuidedImage::class, $guidedModelFQCN);

        $this->app->singleton(
            ConfigProviderContract::class,
            function (): ConfigProviderContract {
                return $this->configProvider;
            }
        );

        $this->app->singleton(
            ImageManagerContract::class,
            ImageManager::class
        );

        $this->app->singleton(
            FileHelperContract::class,
            FileHelper::class
        );

        $logger = $this->createLogger();

        $this->app->singleton(
            ImageUploaderContract::class,
            fn (): ImageUploaderContract => new ImageUploader(
                $this->configProvider,
                resolve(FilesystemManager::class),
                resolve(FileHelperContract::class),
                resolve(ValidationFactory::class),
                resolve(GuidedImage::class),
                $logger,
            )
        );

        $this->app->singleton(
            ImageDispenserContract::class,
            fn (): ImageDispenserContract => new ImageDispenser(
                $this->configProvider,
                resolve(FilesystemManager::class),
                resolve(ImageManagerContract::class),
                $logger,
                resolve(FileHelperContract::class)
            )
        );
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

        if (! $this->app->routesAreCached()) {
            $router->model(strtolower($modelName), $this->configProvider->getGuidedModelNamespace().$modelName);

            require_once sprintf('%s/routes/web.php', $this->getAssetDirectory());
        }
    }

    private function handleMigrations(): void
    {
        $this->loadMigrationsFrom(sprintf('%s/database/migrations', $this->getAssetDirectory()));
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createLogger(): LoggerContract
    {
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = resolve(LoggerFactory::class);

        return $loggerFactory->create($this->getLoggerName(), $this->getLogFileBasename());
    }
}

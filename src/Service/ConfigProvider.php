<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Service;

use ReliqArts\Contract\ConfigProvider as ConfigAccessor;
use ReliqArts\GuidedImage\Contract\ConfigProvider as ConfigProviderContract;

final class ConfigProvider implements ConfigProviderContract
{
    private const CONFIG_KEY_ALLOWED_EXTENSIONS = 'allowed_extensions';
    private const CONFIG_KEY_ROUTES_CONTROLLERS = 'routes.controllers';
    private const CONFIG_KEY_ROUTES_PREFIX = 'routes.prefix';
    private const CONFIG_KEY_GUIDED_MODEL = 'model';
    private const CONFIG_KEY_GUIDED_MODEL_NAMESPACE = 'model_namespace';
    private const CONFIG_KEY_ROUTES_BINDINGS_WITH_GROUP = 'routes.bindings.%s';
    private const CONFIG_KEY_IMAGE_RULES = 'rules';
    private const CONFIG_KEY_IMAGES_TABLE = 'database.image_table';
    private const CONFIG_KEY_IMAGEABLES_TABLE = 'database.imageables_table';
    private const CONFIG_KEY_STORAGE_UPLOAD_DIRECTORY = 'storage.upload_dir';
    private const CONFIG_KEY_STORAGE_CACHE_DISK = 'storage.cache_disk';
    private const CONFIG_KEY_STORAGE_UPLOAD_DISK = 'storage.upload_disk';
    private const CONFIG_KEY_STORAGE_CACHE_DIR = 'storage.cache_dir';
    private const CONFIG_KEY_STORAGE_CACHE_SUB_DIR_RESIZED = 'storage.cache_sub_dir_resized';
    private const CONFIG_KEY_STORAGE_CACHE_SUB_DIR_THUMBS = 'storage.cache_sub_dir_thumbs';
    private const CONFIG_KEY_STORAGE_GENERATE_UPLOAD_DATE_SUB_DIRECTORIES
        = 'storage.generate_upload_date_sub_directories';
    private const CONFIG_KEY_HEADERS_CACHE_DAYS = 'headers.cache_days';
    private const CONFIG_KEY_HEADERS_ADDITIONAL = 'headers.additional';
    private const CONFIG_KEY_IMAGE_ENCODING_FORMAT = 'encoding.format';
    private const CONFIG_KEY_IMAGE_ENCODING_QUALITY = 'encoding.quality';
    private const CONFIG_KEY_DISPENSER_RAW_IMAGE_FALLBACK_ENABLED = 'dispenser.raw_image_fallback_enabled';

    private const DEFAULT_ALLOWED_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png'];
    private const DEFAULT_ROUTES_PREFIX = 'image';
    private const DEFAULT_GUIDED_MODEL = 'Image';
    private const DEFAULT_GUIDED_MODEL_NAMESPACE = 'App\\';
    private const DEFAULT_IMAGES_TABLE = 'images';
    private const DEFAULT_IMAGEABLES_TABLE = 'imageables';
    private const DEFAULT_IMAGE_RULES = 'required|mimes:png,gif,jpeg|max:2048';
    private const DEFAULT_UPLOAD_DIRECTORY = 'uploads/images';
    private const DEFAULT_STORAGE_CACHE_DIR = 'images';
    private const DEFAULT_STORAGE_CACHE_DISK = 'local';
    private const DEFAULT_STORAGE_UPLOAD_DISK = 'public';
    private const DEFAULT_STORAGE_CACHE_SUB_DIR_THUMBS = '.thumbs';
    private const DEFAULT_STORAGE_CACHE_SUB_DIR_RESIZED = '.resized';
    private const DEFAULT_STORAGE_GENERATE_UPLOAD_DATE_SUB_DIRECTORIES = false;
    private const DEFAULT_HEADER_CACHE_DAYS = 366;
    private const DEFAULT_ADDITIONAL_HEADERS = [];
    private const DEFAULT_IMAGE_ENCODING_FORMAT = 'png';
    private const DEFAULT_IMAGE_ENCODING_QUALITY = 90;
    private const DEFAULT_DISPENSER_RAW_IMAGE_FALLBACK_ENABLED = false;

    private const KEY_PREFIX = 'prefix';

    private ConfigAccessor $configAccessor;

    /**
     * ConfigProvider constructor.
     */
    public function __construct(ConfigAccessor $configAccessor)
    {
        $this->configAccessor = $configAccessor;
    }

    public function getAllowedExtensions(): array
    {
        return $this->configAccessor->get(self::CONFIG_KEY_ALLOWED_EXTENSIONS, self::DEFAULT_ALLOWED_EXTENSIONS);
    }

    /**
     * {@inheritdoc}
     *
     * @return array of controller FQCNs for route binding
     */
    public function getControllersForRoutes(): array
    {
        return $this->configAccessor->get(self::CONFIG_KEY_ROUTES_CONTROLLERS, []);
    }

    /**
     * {@inheritdoc}
     *
     * @return string prefix
     */
    public function getRoutePrefix(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_ROUTES_PREFIX, self::DEFAULT_ROUTES_PREFIX);
    }

    /**
     * {@inheritdoc}
     *
     * @return string model name
     */
    public function getGuidedModelName(bool $lowered = false): string
    {
        $model = $this->configAccessor->get(self::CONFIG_KEY_GUIDED_MODEL, self::DEFAULT_GUIDED_MODEL);

        return $lowered ? strtolower($model) : $model;
    }

    /**
     * {@inheritdoc}
     *
     * @return string model namespace
     */
    public function getGuidedModelNamespace(bool $lowered = false): string
    {
        $modelNamespace = $this->configAccessor->get(
            self::CONFIG_KEY_GUIDED_MODEL_NAMESPACE,
            self::DEFAULT_GUIDED_MODEL_NAMESPACE
        );

        return $lowered ? strtolower($modelNamespace) : $modelNamespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteGroupBindings(array $bindings = [], string $groupKey = self::ROUTE_GROUP_KEY_PUBLIC): array
    {
        $defaults = self::ROUTE_GROUP_KEY_PUBLIC === $groupKey ? [self::KEY_PREFIX => $this->getRoutePrefix()] : [];

        $bindings = array_merge(
            $this->configAccessor->get(sprintf(self::CONFIG_KEY_ROUTES_BINDINGS_WITH_GROUP, $groupKey), []),
            $bindings
        );

        return array_merge($defaults, $bindings);
    }

    public function getImageRules(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_IMAGE_RULES, self::DEFAULT_IMAGE_RULES);
    }

    /**
     * {@inheritdoc}
     */
    public function getImagesTableName(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_IMAGES_TABLE, self::DEFAULT_IMAGES_TABLE);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageablesTableName(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_IMAGEABLES_TABLE, self::DEFAULT_IMAGEABLES_TABLE);
    }

    public function getUploadDirectory(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_STORAGE_UPLOAD_DIRECTORY, self::DEFAULT_UPLOAD_DIRECTORY);
    }

    public function generateUploadDateSubDirectories(): bool
    {
        return (bool)$this->configAccessor->get(
            self::CONFIG_KEY_STORAGE_GENERATE_UPLOAD_DATE_SUB_DIRECTORIES,
            self::DEFAULT_STORAGE_GENERATE_UPLOAD_DATE_SUB_DIRECTORIES
        );
    }

    public function getResizedCachePath(): string
    {
        $cacheDir = $this->getCacheDirectory();
        $cacheResizedSubDir = $this->configAccessor->get(
            self::CONFIG_KEY_STORAGE_CACHE_SUB_DIR_RESIZED,
            self::DEFAULT_STORAGE_CACHE_SUB_DIR_RESIZED
        );

        return sprintf('%s/%s', $cacheDir, $cacheResizedSubDir);
    }

    public function getThumbsCachePath(): string
    {
        $cacheDir = $this->getCacheDirectory();
        $cacheThumbsSubDir = $this->configAccessor->get(
            self::CONFIG_KEY_STORAGE_CACHE_SUB_DIR_THUMBS,
            self::DEFAULT_STORAGE_CACHE_SUB_DIR_THUMBS
        );

        return sprintf('%s/%s', $cacheDir, $cacheThumbsSubDir);
    }

    public function getCacheDaysHeader(): int
    {
        return (int)$this->configAccessor->get(self::CONFIG_KEY_HEADERS_CACHE_DAYS, self::DEFAULT_HEADER_CACHE_DAYS);
    }

    public function getAdditionalHeaders(): array
    {
        return $this->configAccessor->get(self::CONFIG_KEY_HEADERS_ADDITIONAL, self::DEFAULT_ADDITIONAL_HEADERS);
    }

    public function getCacheDirectory(): string
    {
        return $this->configAccessor->get(
            self::CONFIG_KEY_STORAGE_CACHE_DIR,
            self::DEFAULT_STORAGE_CACHE_DIR
        );
    }

    public function getImageEncodingFormat(): string
    {
        return $this->configAccessor->get(
            self::CONFIG_KEY_IMAGE_ENCODING_FORMAT,
            self::DEFAULT_IMAGE_ENCODING_FORMAT
        );
    }

    public function getImageEncodingQuality(): int
    {
        return (int)$this->configAccessor->get(
            self::CONFIG_KEY_IMAGE_ENCODING_QUALITY,
            self::DEFAULT_IMAGE_ENCODING_QUALITY
        );
    }

    public function getCacheDiskName(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_STORAGE_CACHE_DISK, self::DEFAULT_STORAGE_CACHE_DISK);
    }

    public function getUploadDiskName(): string
    {
        return $this->configAccessor->get(self::CONFIG_KEY_STORAGE_UPLOAD_DISK, self::DEFAULT_STORAGE_UPLOAD_DISK);
    }

    public function isRawImageFallbackEnabled(): bool
    {
        return $this->configAccessor->get(
            self::CONFIG_KEY_DISPENSER_RAW_IMAGE_FALLBACK_ENABLED,
            self::DEFAULT_DISPENSER_RAW_IMAGE_FALLBACK_ENABLED
        );
    }
}

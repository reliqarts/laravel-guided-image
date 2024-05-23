<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

interface ConfigProvider
{
    public const ROUTE_GROUP_KEY_PUBLIC = 'public';

    public function getAllowedExtensions(): array;

    /**
     * Get list of controllers onto which guided image routes should be bound.
     *
     * @return array of controller FQCNs for route binding
     */
    public function getControllersForRoutes(): array;

    /**
     * Get route prefix for guided image routes.
     *
     * @return string prefix
     */
    public function getRoutePrefix(): string;

    /**
     * Get image model for guided image routes.
     *
     * @param  bool  $lowered  whether model should be returned in lowercase form
     * @return string model name
     */
    public function getGuidedModelName(bool $lowered = false): string;

    /**
     * Get image model namespace for guided image routes.
     *
     * @param  bool  $lowered  whether model should be returned in lowercase form
     * @return string model namespace
     */
    public function getGuidedModelNamespace(bool $lowered = false): string;

    /**
     * Get bindings for public routes.
     */
    public function getRouteGroupBindings(array $bindings = [], string $groupKey = self::ROUTE_GROUP_KEY_PUBLIC): array;

    public function getImageRules(): string;

    /**
     * Get guided image table.
     */
    public function getImagesTableName(): string;

    /**
     * Get guided imageables table.
     */
    public function getImageablesTableName(): string;

    public function getUploadDirectory(): string;

    public function generateUploadDateSubDirectories(): bool;

    public function getResizedCachePath(): string;

    public function getThumbsCachePath(): string;

    public function getCacheDaysHeader(): int;

    public function getAdditionalHeaders(): array;

    public function getCacheDirectory(): string;

    public function getImageEncodingMimeType(): string;

    public function getImageEncodingQuality(): int;

    public function getCacheDiskName(): string;

    public function getUploadDiskName(): string;

    public function isRawImageFallbackEnabled(): bool;
}

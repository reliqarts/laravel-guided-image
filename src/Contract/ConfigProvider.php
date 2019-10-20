<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

interface ConfigProvider
{
    public const ROUTE_GROUP_KEY_PUBLIC = 'public';

    /**
     * @return array
     */
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
     * @param bool $lowered whether model should be returned in lowercase form
     *
     * @return string model name
     */
    public function getGuidedModelName(bool $lowered = false): string;

    /**
     * Get image model namespace for guided image routes.
     *
     * @param bool $lowered whether model should be returned in lowercase form
     *
     * @return string model namespace
     */
    public function getGuidedModelNamespace(bool $lowered = false): string;

    /**
     * Get bindings for public routes.
     *
     * @param array  $bindings
     * @param string $groupKey
     *
     * @return array
     */
    public function getRouteGroupBindings(array $bindings = [], string $groupKey = self::ROUTE_GROUP_KEY_PUBLIC): array;

    /**
     * @return string
     */
    public function getImageRules(): string;

    /**
     * Get guided image table.
     *
     * @return string
     */
    public function getImagesTableName(): string;

    /**
     * Get guided imageables table.
     *
     * @return string
     */
    public function getImageablesTableName(): string;

    /**
     * @return string
     */
    public function getUploadDirectory(): string;

    /**
     * @return bool
     */
    public function generateUploadDateSubDirectories(): bool;

    /**
     * @return string
     */
    public function getResizedCachePath(): string;

    /**
     * @return string
     */
    public function getThumbsCachePath(): string;

    /**
     * @return int
     */
    public function getCacheDaysHeader(): int;

    /**
     * @return array
     */
    public function getAdditionalHeaders(): array;

    /**
     * @return string
     */
    public function getCacheDirectory(): string;

    /**
     * @return string
     */
    public function getImageEncodingFormat(): string;

    /**
     * @return int
     */
    public function getImageEncodingQuality(): int;

    /**
     * @return string
     */
    public function getCacheDiskName(): string;

    /**
     * @return string
     */
    public function getUploadDiskName(): string;
}

<?php

namespace ReliQArts\GuidedImage\Helpers;

use Config as BaseConfig;

class Config extends BaseConfig
{
    /**
     * Get list of controllers onto which guided image routes should be binded.
     *
     * @param array $controllers preset controllers for guided route binds
     *
     * @return array of controllers for route binding
     */
    public static function getContollersForRoutes(array $controllers = []): array
    {
        return $controllers = array_merge(parent::get('guidedimage.routes.controllers', []), []);
    }

    /**
     * Get route prefix for guided image routes.
     *
     * @return string prefix
     */
    public static function getRoutePrefix(): string
    {
        return parent::get('guidedimage.routes.prefix', 'image');
    }

    /**
     * Get image model for guided image routes.
     *
     * @param bool|bool $lowered whether model should be returned in lowercase form
     *
     * @return string model
     */
    public static function getRouteModel($lowered = false): string
    {
        $routeModel = parent::get('guidedimage.model', 'Image');

        return $lowered ? strtolower($routeModel) : $routeModel;
    }

    /**
     * Get image model namespace for guided image routes.
     *
     * @param bool|bool $lowered whether model should be returned in lowercase form
     *
     * @return string model
     */
    public static function getRouteModelNamespace($lowered = false): string
    {
        $routeModelNamespace = parent::get('guidedimage.model_namespace', 'App\\');

        return $lowered ? strtolower($routeModelNamespace) : $routeModelNamespace;
    }

    /**
     * Get bindings for public routes.
     *
     * @param mixed $bindings
     * @param mixed $groupKey
     *
     * @return array
     */
    public static function getRouteGroupBindings($bindings = [], $groupKey = 'public'): array
    {
        $defaults = ('public' === $groupKey) ? ['prefix' => self::getRoutePrefix()] : [];
        $bindings = array_merge(parent::get("guidedimage.routes.bindings.{$groupKey}", []), $bindings);

        return array_merge($defaults, $bindings);
    }

    /**
     * Get guided image table.
     *
     * @return string
     */
    public static function getImageTable(): string
    {
        return parent::get('guidedimage.database.image_table', 'images');
    }

    /**
     * Get guided imageables table.
     *
     * @return string
     */
    public static function getImageablesTable(): string
    {
        return parent::get('guidedimage.database.imageables_table', 'imageables');
    }
}

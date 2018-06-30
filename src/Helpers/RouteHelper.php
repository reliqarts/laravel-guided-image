<?php

namespace ReliQArts\GuidedImage\Helpers;

use Config;

class RouteHelper
{
    /**
     * Get list of controllers onto which guided image routes should be binded.
     *
     * @param array $controllers preset controllers for guided route binds
     *
     * @return array array of controllers for route binding
     */
    public static function getContollersForRoutes(array $controllers = [])
    {
        return $controllers = array_merge(Config::get('guidedimage.routes.controllers', []), []);
    }

    /**
     * Get route prefix for guided image routes.
     *
     * @return string prefix
     */
    public static function getRoutePrefix()
    {
        return Config::get('guidedimage.routes.prefix', 'image');
    }

    /**
     * Get image model for guided image routes.
     *
     * @param bool|bool $lowered whether model should be returned in lowercase form
     *
     * @return string model
     */
    public static function getRouteModel($lowered = false)
    {
        $routeModel = Config::get('guidedimage.model', 'Image');

        return $lowered ? strtolower($routeModel) : $routeModel;
    }

    /**
     * Get image model namespace for guided image routes.
     *
     * @param bool|bool $lowered whether model should be returned in lowercase form
     *
     * @return string model
     */
    public static function getRouteModelNamespace($lowered = false)
    {
        $routeModelNamespace = Config::get('guidedimage.model_namespace', 'App\\');

        return $lowered ? strtolower($routeModelNamespace) : $routeModelNamespace;
    }

    /**
     * Get bindings for public routes.
     *
     * @param mixed $bindings
     * @param mixed $groupKey
     */
    public static function getRouteGroupBindings($bindings = [], $groupKey = 'public')
    {
        $defaults = ('public' == $groupKey) ? ['prefix' => self::getRoutePrefix()] : [];
        $bindings = array_merge(Config::get("guidedimage.routes.bindings.{$groupKey}", []), $bindings);

        return array_merge($defaults, $bindings);
    }
}

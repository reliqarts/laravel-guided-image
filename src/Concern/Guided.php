<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concern;

use Illuminate\Support\Str;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\GuidedImage as GuidedImageContract;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use ReliqArts\GuidedImage\Exception\BadImplementation;

/**
 * Trait Guided.
 *
 * @property mixed $id
 * @property string name
 * @property string full_path
 */
trait Guided
{
    use GuidedRepository;

    /**
     * @throws BadImplementation
     */
    public static function bootGuided()
    {
        $implementedInterfaces = class_implements(static::class);

        if (!in_array(GuidedImageContract::class, $implementedInterfaces, true)) {
            throw new BadImplementation(
                sprintf('Model (%s) must implement `%s` to be guided!', static::class, GuidedImageContract::class)
            );
        }
    }

    /**
     * Get resized/thumbnail photo link.
     *
     * @param string $type   request type (thumbnail or resize)
     * @param array  $params parameters to pass to route
     *
     * @return string
     */
    public function getRoutedUrl(string $type, array $params = []): string
    {
        if (empty($params)) {
            return $this->getUrl();
        }

        /**
         * @var ConfigProvider
         */
        $configProvider = resolve(ConfigProvider::class);
        $guidedModelName = $configProvider->getGuidedModelName(true);

        array_unshift($params, $this->id);

        return route(sprintf('%s.%s', $guidedModelName, $type), $params);
    }

    /**
     * Whether image is safe for deleting.
     * Since a single image may be re-used this method is used to determine
     * when an image can be safely deleted from disk.
     *
     * @param int $safeAmount a photo is safe to delete if it is used by $safe_num amount of records
     *
     * @return bool whether image is safe for delete
     */
    public function isSafeForDelete(int $safeAmount = 1): bool
    {
        return $safeAmount === 1;
    }

    /**
     * Get link to resized photo.
     *
     * @param array $params parameters to pass to route
     *
     * @return string
     */
    public function routeResized(array $params = []): string
    {
        return $this->getRoutedUrl(Resize::ROUTE_TYPE_NAME, $params);
    }

    /**
     * Get link to photo thumbnail.
     *
     * @param array $params parameters to pass to route
     *
     * @return string
     */
    public function routeThumbnail(array $params = []): string
    {
        return $this->getRoutedUrl(Thumbnail::ROUTE_TYPE_NAME, $params);
    }

    /**
     * Get class.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return get_class($this);
    }

    /**
     *  Get ready URL to image.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return urldecode($this->getFullPath());
    }

    /**
     *  Get ready image title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return Str::title(preg_replace('/[\\-_]/', ' ', $this->getName()));
    }

    /**
     * Get full path.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->full_path ?? '';
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }
}

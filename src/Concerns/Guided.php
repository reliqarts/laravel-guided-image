<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Concerns;

use Illuminate\Support\Str;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;
use ReliqArts\GuidedImage\Contracts\Guided as GuidedContract;
use ReliqArts\GuidedImage\Exceptions\BadImplementation;

/**
 * Trait Guided.
 *
 * @property mixed $id
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

        if (!in_array(GuidedContract::class, $implementedInterfaces, true)) {
            throw new BadImplementation(
                sprintf('Model (%s) must implement `%s` to be guided!', static::class, GuidedContract::class)
            );
        }
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
        return true;
    }

    /**
     * Get routed link to photo.
     *
     * @param array  $params parameters to pass to route
     * @param string $type   Operation to be performed on instance. (resize, thumb)
     *
     * @return string
     */
    public function routeResized(array $params = null, string $type = 'resize'): string
    {
        /**
         * @var ConfigProvider
         */
        $configProvider = resolve(ConfigProvider::class);
        $guidedModelName = $configProvider->getGuidedModelName(true);

        if (!(in_array($type, ['resize', 'thumb'], true) && is_array($params))) {
            return $this->getUrl();
        }
        array_unshift($params, $this->id);

        return route(sprintf('%s.%s', $guidedModelName, $type), $params);
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

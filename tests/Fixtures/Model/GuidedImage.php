<?php

/** @noinspection PhpTooManyParametersInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Fixtures\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use ReliqArts\GuidedImage\Contract\GuidedImage as GuidedImageContract;

abstract class GuidedImage implements GuidedImageContract
{
    /**
     * {@inheritdoc}
     */
    abstract public function where($column, $operator = null, $value = null, $boolean = 'and'): Builder;

    /**
     * {@inheritdoc}
     */
    abstract public function unguard($state = true);

    /**
     * {@inheritdoc}
     */
    abstract public function reguard();

    /**
     * @return GuidedImageContract|Model|void
     */
    abstract public function create(array $attributes = []);
}

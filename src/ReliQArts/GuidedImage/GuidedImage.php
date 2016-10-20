<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ReliQArts\GuidedImage;

use Illuminate\Database\Eloquent\Model;
use ReliQArts\GuidedImage\Traits\Guided as GuidedTrait;
use ReliQArts\GuidedImage\Contracts\Guided as GuidedContract;

/**
 *  GuidedImage dummy model.
 */
class GuidedImage extends Model implements GuidedContract
{
    use GuidedTrait;
}

<?php

namespace ReliQArts\GuidedImage;

use Illuminate\Database\Eloquent\Model;
use ReliQArts\GuidedImage\Contracts\Guided as GuidedContract;
use ReliQArts\GuidedImage\Traits\Guided as GuidedTrait;

/**
 *  GuidedImage dummy model.
 */
class GuidedImage extends Model implements GuidedContract
{
    use GuidedTrait;
}

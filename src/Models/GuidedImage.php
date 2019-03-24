<?php

namespace ReliqArts\GuidedImage\Models;

use Illuminate\Database\Eloquent\Model;
use ReliqArts\GuidedImage\Contracts\Guided as GuidedContract;
use ReliqArts\GuidedImage\Traits\Guided as GuidedTrait;

/**
 *  GuidedImage dummy model.
 */
class GuidedImage extends Model implements GuidedContract
{
    use GuidedTrait;
}

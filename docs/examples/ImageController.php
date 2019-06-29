<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Examples\Http\Controllers;

use ReliqArts\GuidedImage\Contracts\ImageGuide;
use ReliqArts\GuidedImage\Concerns\Guide;

class ImageController implements ImageGuide
{
    use Guide;
}

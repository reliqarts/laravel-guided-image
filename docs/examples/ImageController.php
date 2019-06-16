<?php

declare(strict_types=1);

namespace Examples\Http\Controllers;

use ReliqArts\GuidedImage\Contracts\Guide;
use ReliqArts\GuidedImage\Concerns\Guide as GuideTrait;

class ImageController implements Guide
{
    use GuideTrait;
}

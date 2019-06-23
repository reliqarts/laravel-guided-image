<?php

declare(strict_types=1);

namespace Examples\Http\Controllers;

use ReliqArts\GuidedImage\Concerns\Guide as GuideTrait;
use ReliqArts\GuidedImage\Contracts\Guide;

class ImageController implements Guide
{
    use GuideTrait;
}

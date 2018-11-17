<?php

namespace App\Http\Controllers;

use ReliQArts\GuidedImage\Contracts\ImageGuider as ImageGuiderContract;
use ReliQArts\GuidedImage\Traits\ImageGuider as ImageGuiderTrait;

class ImageController extends Controller implements ImageGuiderContract
{
    use ImageGuiderTrait;
}

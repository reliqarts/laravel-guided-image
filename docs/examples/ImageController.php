<?php

namespace App\Http\Controllers;

use ReliQArts\GuidedImage\Traits\ImageGuider as ImageGuiderTrait;
use ReliQArts\GuidedImage\Contracts\ImageGuider as ImageGuiderContract;

class ImageController extends Controller implements ImageGuiderContract
{
    use ImageGuiderTrait;
}

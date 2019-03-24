<?php

namespace App\Http\Controllers;

use ReliqArts\GuidedImage\Contracts\ImageGuider as ImageGuiderContract;
use ReliqArts\GuidedImage\Traits\ImageGuider as ImageGuiderTrait;

class ImageController extends Controller implements ImageGuiderContract
{
    use ImageGuiderTrait;
}

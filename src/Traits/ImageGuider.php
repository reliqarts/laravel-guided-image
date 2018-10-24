<?php

namespace ReliQArts\GuidedImage\Traits;

use File;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Facades\Image;
use Illuminate\Config\Repository as Config;
use ReliQArts\GuidedImage\ViewModels\Result;
use Intervention\Image\Image as InterventionImage;
use Intervention\Image\Exception\NotReadableException;
use ReliQArts\GuidedImage\Contracts\Guided as GuidedContract;

/**
 * Guide by acquiring these traits.
 *
 * @author Patrick Reid (@IAmReliQ)
 *
 * @since  2016
 *
 * @uses \Intervention\Image\Facades\Image to manipulate images.
 * @uses \ReliQArts\GuidedImage\ViewModels\Result
 */
trait ImageGuider
{
    /**
     * List of headers.
     */
    protected $headers = [];

    /**
     * Guided image cache directory.
     */
    protected $skimDir;

    /**
     * Thumbnail cache directory.
     */
    protected $skimThumbs;

    /**
     * Resized images cache directory.
     */
    protected $skimResized;

    /**
     * Route values to be treated as null.
     */
    protected $nulls = [false, null, 'null'];

    /**
     * Constructor. Some prep.
     */
    public function __construct(Config $config)
    {
        $this->skimDir = storage_path($config->get('guidedimage.storage.skim_dir'));
        $this->skimThumbs = "{$this->skimDir}/".$config->get('guidedimage.storage.skim_thumbs');
        $this->skimResized = "{$this->skimDir}/".$config->get('guidedimage.storage.skim_resized');
        $this->nulls = array_merge($this->nulls, $config->get('guidedimage.routes.nulls', []));

        // create or avail needed directories
        if (!File::isDirectory($this->skimThumbs)) {
            File::makeDirectory($this->skimThumbs, 0777, true);
        }
        if (!File::isDirectory($this->skimResized)) {
            File::makeDirectory($this->skimResized, 0777, true);
        }

        // setup preliminary headers
        $maxAge = 60 * 60 * 24 * $config->get('guidedimage.headers.cache_days'); // x days
        // default headers
        $this->headers = array_merge([
            'Cache-Control' => "public, max-age=${maxAge}",
        ], $config->get('guidedimage.headers.additional', []));
    }

    /**
     * Empty skim cache by removing SkimDir.
     *
     * @param Request $request
     *
     * @return ViewModels\Result
     */
    public function emptyCache(Request $request)
    {
        if (!$request->ajax()) {
            return 'Use JSON.';
        }

        $result = new Result();
        if (File::deleteDirectory($this->skimDir, true)) {
            $result->success = true;
            $result->message = 'Image directory cache successfully cleared.';
        } else {
            $result->message = 'Image directory cache could not be cleared.';
            $result->error = $result->message;
        }

        return response()->json($result);
    }

    /**
     * Get a thumbnail.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param string  $method      crop|fit
     * @param int     $width
     * @param int     $height
     * @param bool    $object      whether Intervention Image should be returned
     *
     * @return \Intervention\Image\Facades\Image|string intervention Image object or actual image url
     */
    public function thumb(Request $request, GuidedContract $guidedImage, $method, $width, $height, $object = false)
    {
        $width = (in_array($width, $this->nulls, true)) ? null : $width;
        $height = (in_array($height, $this->nulls, true)) ? null : $height;
        $object = (in_array($object, $this->nulls, true)) ? null : true;

        $skimFile = "{$this->skimThumbs}/${width}-${height}-_-_".$guidedImage->getName();

        // accept methods crop and thumb
        $acceptMethods = ['crop', 'fit'];
        if (!in_array($method, $acceptMethods, true)) {
            abort(404);
        }
        // Get intervention image
        try {
            if (!File::exists($skimFile)) {
                $image = Image::make($guidedImage->getUrl())->{$method}($width, $height);
                $image->save($skimFile);
            } else {
                $image = Image::make($skimFile);
            }
        } catch (NotReadableException $e) {
            abort(404);
        }

        // Setup response with appropriate headers
        return ($object) ? $image : new Response(
            File::get($skimFile),
            200,
            $this->getImageHeaders($request, $image) ?: []
        );

        // Return object or actual image
    }

    /**
     * Get a resized Guided Image.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param int     $width
     * @param int     $height
     * @param bool    $aspect      Keep aspect ratio?
     * @param bool    $upsize      Allow upsize?
     * @param bool    $object      whether Intervention Image should be returned
     *
     * @return \Intervention\Image\Facades\Image|string intervention Image object or actual image url
     */
    public function resized(
        Request $request,
        GuidedContract $guidedImage,
        $width,
        $height,
        $aspect = true,
        $upsize = false,
        $object = false
    ) {
        $width = (in_array($width, $this->nulls, true)) ? null : $width;
        $height = (in_array($height, $this->nulls, true)) ? null : $height;
        $aspect = (in_array($aspect, $this->nulls, true)) ? true : false;
        $upsize = (in_array($upsize, $this->nulls, true)) ? false : true;
        $object = (in_array($object, $this->nulls, true)) ? false : true;

        $skimFile = "{$this->skimResized}/${width}-${height}-_-_".$guidedImage->getName();
        $image = false;

        // Get intervention image
        try {
            if (!File::exists($skimFile)) {
                $image = Image::make($guidedImage->getUrl());
                $image->resize($width, $height, function ($constraint) use ($aspect, $upsize) {
                    if ($aspect) {
                        $constraint->aspectRatio();
                    }
                    if ($upsize) {
                        $constraint->upsize();
                    }
                });
                $image->save($skimFile);
            } else {
                $image = Image::make($skimFile);
            }
        } catch (NotReadableException $e) {
            $image = false;
        }

        // if no image; abort
        if (!$image) {
            abort(404);
        }

        // Setup response with appropriate headers
        return ($object) ? $image : new Response(
            File::get($skimFile),
            200,
            $this->getImageHeaders($request, $image) ?: []
        );

        // Return object or actual image
    }

    /**
     * Get dummy Guided.
     *
     * @param int    $width
     * @param int    $height
     * @param string $color
     * @param bool   $fill
     * @param mixed  $object
     *
     * @return \Intervention\Image\Facades\Image|string intervention Image object or actual image url
     */
    public function dummy($width, $height, $color = '#eefefe', $fill = false, $object = false)
    {
        $width = (in_array($width, $this->nulls, true)) ? null : $width;
        $height = (in_array($height, $this->nulls, true)) ? null : $height;
        $color = (in_array($color, $this->nulls, true)) ? null : $color;
        $fill = (in_array($fill, $this->nulls, true)) ? null : $fill;
        $object = (in_array($object, $this->nulls, true)) ? false : true;

        $img = Image::canvas($width, $height, $color);
        $image = ($fill) ? $img->fill($fill) : $img;

        // Return object or actual image
        return ($object) ? $image : $image->response();
    }

    /**
     * Get image headers. Improved caching
     * If the image has not been modified say 304 Not Modified.
     *
     * @param \Intervention\Image\Facades\Image $image
     *
     * @return array image headers
     */
    private function getImageHeaders(Request $request, InterventionImage $image)
    {
        $filePath = "{$image->dirname}/{$image->basename}";
        $lastModified = File::lastModified($filePath);
        $modifiedSince = ($request->header('If-Modified-Since')) ? $request->header('If-Modified-Since') : false;
        $etagHeader = ($request->header('If-None-Match')) ? trim($request->header('If-None-Match')) : null;
        $etagFile = md5_file($filePath);

        // check if image hasn't changed
        if (@strtotime($modifiedSince) === $lastModified || $etagFile === $etagHeader) {
            // Say not modified and kill script
            header('HTTP/1.1 304 Not Modified');
            header("ETag: ${etagFile}");
            exit;
        }

        // adjust headers and return
        return $this->headers = array_merge($this->headers, [
            'Content-Type'        => $image->mime,
            'Content-Disposition' => 'inline; filename='.$image->filename,
            'Last-Modified'       => date(DATE_RFC822, $lastModified),
            'Etag'                => $etagFile,
        ]);
    }
}

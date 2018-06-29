<?php

namespace ReliQArts\GuidedImage\Traits;

use URL;
use File;
use Config;
use Validator;
use ReliQArts\GuidedImage\ViewModels\Result;
use ReliQArts\GuidedImage\Helpers\RouteHelper;
use ReliQArts\GuidedImage\Exceptions\ImplementationException;

/**
 * Get guided by acquiring these traits.
 *
 * @author Patrick Reid (@IAmReliQ)
 *
 * @since  2016
 *
 * @uses \ReliQArts\GuidedImage\ViewModels\Result;
 */
trait Guided
{
    /**
     * The rules that govern a guided image.
     */
    public static $rules = ['file' => 'required|mimes:png,gif,jpeg|max:2048'];
    /**
     * Class instance.
     *
     * @var stdClass
     */
    private $class;

    /**
     * Mandatory ancestor eloguent model.
     *
     * @var string
     */
    private $eloquentAncestor = 'Illuminate\Database\Eloquent\Model';

    /**
     * Ensure things are ready.
     */
    public function __construct(array $attributes = [])
    {
        $this->class = get_class($this);
        // Instance must be of class which extends Eloquent Model.
        if (! is_subclass_of($this, $this->eloquentAncestor)) {
            throw new ImplementationException("Guided model ({$this->class}) must extend {$this->eloquentAncestor}.");
        }

        parent::__construct($attributes);
    }

    /**
     * Retrieve the creator (uploader) of the image.
     */
    public function creator()
    {
        return $this->belongsTo('App\User', 'creator_id');
    }

    /**
     * Whether image is safe for deleting.
     * Since a single image may be re-used this method is used to determine when an image can be safely deleted from disk.
     *
     * @param int $safeAmount a photo is safe to delete if it is used by $safe_num amount of records
     *
     * @return bool whether image is safe for delete
     */
    public function isSafeForDelete($safeAmount = 1)
    {
        return true;
    }

    /**
     * Removes image from database, and filesystem, if not in use.
     *
     * @param bool $force override safety constraints
     *
     * @return \ReliQArts\GuidedImage\ViewModels\Result result object
     */
    public function remove($force = false)
    {
        $result = new Result();
        $img_name = $this->getName();
        $safe = $this->isSafeForDelete();

        if ($safe || $force) {
            if (File::delete(urldecode($this->getFullPath()))) {
                $this->delete();
            }
            $result->success = true;
        } else {
            $result->message = 'Not safe to delete, hence file not removed.';
        }

        return $result;
    }

    /**
     * Get routed link to photo.
     *
     * @param array  $params parameters to pass to route
     * @param string $type   Operation to be performed on instance. (resize, thumb)
     */
    public function routeResized(array $params = null, $type = 'resize')
    {
        $guidedModel = strtolower(RouteHelper::getRouteModel(true));

        if (! (in_array($type, ['resize', 'thumb']) && is_array($params))) {
            return $this->url();
        }
        array_unshift($params, $this->id);

        return route("{$guidedModel}.{$type}", $params);
    }

    /**
     * Get class.
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     *  Get ready URL to image.
     */
    public function getUrl()
    {
        return urldecode($this->getFullPath());
    }

    /**
     *  Get ready image title.
     */
    public function getTitle()
    {
        return title_case(preg_replace('/[\\-_]/', ' ', $this->getName()));
    }

    /**
     * Get full path.
     */
    public function getFullPath()
    {
        return $this->full_path;
    }

    /**
     * Get name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get upload directory.
     *
     * @return string upload directory
     */
    public static function getUploadDir()
    {
        return Config::get('guidedimage.upload_dir');
    }

    /**
     *  Upload and save image.
     *
     * @param \Illuminate\Http\UploadedFile|Symfony\Component\HttpFoundation\File\UploadedFile $imageFile Actual file from request. e.g. $request->file('image');
     *
     * @return \ReliQArts\GuidedImage\ViewModels\Result result object
     */
    public static function upload($imageFile)
    {
        $result = new Result();
        $validator = Validator::make(['file' => $imageFile], self::$rules);
        $extWhitelist = Config::get('guidedimage.allowed_extensions', ['gif', 'jpg', 'jpeg', 'png']);
        $result->message = 'Invalid file size or type.';
        $result->error = 'Invalid image.';

        if ($validator->passes()) {
            $size = $imageFile->getSize();
            $mimeType = $imageFile->getMimeType();
            $extension = $imageFile->getClientOriginalExtension();
            $fullName = $imageFile->getClientOriginalName();
            $filePathInfo = pathinfo($fullName);
            $filename = str_slug($filePathInfo['filename']);
            $existing = self::where('name', $filename)->where('size', $size);

            // explicitly check extension against whitelist
            if (in_array(strtolower($extension), $extWhitelist)) {
                if (! $existing->count()) {
                    $im['size'] = $size;
                    $im['name'] = $filename;
                    $im['mime_type'] = $mimeType;
                    $im['extension'] = $extension;
                    $im['location'] = self::getUploadDir();
                    $im['creator_id'] = auth()->user()->id;
                    $im['full_path'] = urlencode($im['location'].'/'.$filename.'.'.$im['extension']);
                    list($im['width'], $im['height']) = getimagesize($imageFile);

                    try {
                        $file = $imageFile->move($im['location'], $im['name'].'.'.$im['extension']);
                        $newImage = new self();

                        // file moved, save
                        $newImage->fill($im);
                        if ($newImage->save()) {
                            $result->success = true;
                            $result->extra = $newImage;
                        }
                    } catch (Exception $e) {
                        $result->error = $e->getMessage();
                        $result->message = null;
                    }
                } else {
                    $result->success = true;
                    $result->extra = $existing->first();
                    $result->message = 'Image reused.';
                }
            }
        }

        return $result;
    }
}

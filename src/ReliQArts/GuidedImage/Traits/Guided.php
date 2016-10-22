<?php

/**
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ReliQArts\GuidedImage\Traits;

use URL;
use File;
use Config;
use Schema;
use Validator;
use ErrorException;
use ReliQArts\GuidedImage\ViewModels\Result;
use ReliQArts\GuidedImage\Helpers\RouteHelper;
use ReliQArts\GuidedImage\Exceptions\ImplementationException;

/**
 * Get guided by acquiring these traits.
 *
 * @author Patrick Reid (@IAmReliQ)
 * @since  2016
 * @uses ReliQArts\GuidedImage\ViewModels\Result;
 */
trait Guided
{
    // Class instance.
    private $class;

    // Mandatory ancestor eloguent model.
    private $eloquentAncestor = 'Illuminate\Database\Eloquent\Model';

    /**
     * The rules that govern a guided image.
     */
    public static $rules = ['file' => 'required|mimes:png,gif,jpeg|max:2048'];

    /**
     * Ensure things are ready.
     */
    public function __construct()
    {
        $this->class = get_class($this);

        // Instance must be of class which extends Eloquent Model.
        if (!is_subclass_of($this, $this->eloquentAncestor)) {
            throw new ImplementationException("Guided model ({$this->class}) must extend {$this->eloquentAncestor}.");
        }
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
     * @param int $safeAmount A photo is safe to delete if it is used by $safe_num amount of records.
     * @return bool|boolean Whether image is safe for delete.
     */
    public function isSafeForDelete($safeAmount = 1)
    {
        return true;
    }

    /**
     *  Removes image from database, and filesystem, if not in use.
     *  @param $force       Override safety constraints.
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
     * @param array $params Parameters to pass to route.
     * @param string $type Operation to be performed on instance. (resize, thumb)
     */
    public function routeResized($params = false, $type = 'resize')
    {
        $guidedModel = strtolower(RouteHelper::getRouteModel(true));

        if (!(in_array($type, ['resize', 'thumb'])) && is_array($params)) {
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
        return title_case(preg_replace("/[\-_]/", ' ', $this->getName()));
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
     * @param Config $config App config.
     */
    public static function getUploadDir()
    {
        return Config::get('guidedimage.upload_dir');
    }

    /**
     *  Upload and save image.
     */
    public static function upload($imageFile)
    {
        $validator = Validator::make(['file' => $imageFile], self::$rules);
        $result = new Result();

        if ($validator->passes()) {
            $full_name = $imageFile->getClientOriginalName();
            $file_spl = pathinfo($full_name);
            $filename = str_slug($file_spl['filename']);
            $existing = self::where('name', $filename)
                            ->where('size', $imageFile->getSize());

            if (!$existing->count()) {
                $im['extension'] = $imageFile->getClientOriginalExtension();
                $im['mime_type'] = $imageFile->getMimeType();
                $im['size'] = $imageFile->getSize();
                $im['name'] = $filename;
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
                }
            } else {
                $result->success = true;
                $result->extra = $existing->first();
                $result->message = 'Image reused.';
            }
        } else {
            $result->error = 'Image not valid.';
            $result->message = 'Image not valid. Please check size.';
        }

        return $result;
    }
}

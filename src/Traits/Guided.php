<?php

namespace ReliqArts\GuidedImage\Traits;

use Exception;
use File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use ReliqArts\GuidedImage\Exceptions\BadImplementation;
use ReliqArts\GuidedImage\Helpers\Config;
use ReliqArts\GuidedImage\ViewModels\Result;
use Validator;

/**
 * Get guided by acquiring these traits.
 *
 * @author Patrick Reid (@IAmReliQ)
 *
 * @since  2016
 *
 * @uses   \ReliqArts\GuidedImage\ViewModels\Result;
 */
trait Guided
{
    /**
     * The rules that govern a guided image.
     */
    public static $rules = ['file' => 'required|mimes:png,gif,jpeg|max:2048'];

    /**
     * Class name.
     *
     * @var string
     */
    private $className;

    /**
     * Mandatory ancestor eloquent model.
     *
     * @var string
     */
    private $eloquentAncestor = 'Illuminate\Database\Eloquent\Model';

    /**
     * Ensure things are ready.
     *
     * @param array $attributes
     *
     * @throws BadImplementation
     */
    public function __construct(array $attributes = [])
    {
        $this->className = get_class($this);
        // Instance must be of class which extends Eloquent Model.
        if (!is_subclass_of($this, $this->eloquentAncestor)) {
            throw new BadImplementation("Guided model ({$this->className}) must extend {$this->eloquentAncestor}.");
        }

        parent::__construct($attributes);
    }

    /**
     * Retrieve the creator (uploader) of the image.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo('App\User', 'creator_id');
    }

    /**
     * Whether image is safe for deleting.
     * Since a single image may be re-used this method is used to determine
     * when an image can be safely deleted from disk.
     *
     * @param int $safeAmount a photo is safe to delete if it is used by $safe_num amount of records
     *
     * @return bool whether image is safe for delete
     */
    public function isSafeForDelete(int $safeAmount = 1): bool
    {
        return true;
    }

    /**
     * Removes image from database, and filesystem, if not in use.
     *
     * @param bool $force override safety constraints
     *
     * @return Result
     */
    public function remove(bool $force = false): Result
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
     *
     * @return string
     */
    public function routeResized(array $params = null, string $type = 'resize'): string
    {
        $guidedModel = strtolower(Config::getRouteModel(true));

        if (!(in_array($type, ['resize', 'thumb'], true) && is_array($params))) {
            return $this->url();
        }
        array_unshift($params, $this->id);

        return route("{$guidedModel}.{$type}", $params);
    }

    /**
     * Get class.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     *  Get ready URL to image.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return urldecode($this->getFullPath());
    }

    /**
     *  Get ready image title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return title_case(preg_replace('/[\\-_]/', ' ', $this->getName()));
    }

    /**
     * Get full path.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->full_path;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get upload directory.
     *
     * @return string upload directory
     */
    public static function getUploadDir(): string
    {
        return Config::get('guidedimage.upload_dir');
    }

    /**
     *  Upload and save image.
     *
     * @param UploadedFile|Symfony\Component\HttpFoundation\File\UploadedFile $imageFile                  File
     *                                                                                                    from
     *                                                                                                    request.e.g.
     *                                                                                                    $request->file('image');
     *
     * @return Result
     */
    public static function upload($imageFile): Result
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
            if (in_array(strtolower($extension), $extWhitelist, true)) {
                if (!$existing->count()) {
                    $im['size'] = $size;
                    $im['name'] = $filename;
                    $im['mime_type'] = $mimeType;
                    $im['extension'] = $extension;
                    $im['location'] = self::getUploadDir();
                    $im['creator_id'] = auth()->user()->id;
                    $im['full_path'] = urlencode($im['location'] . '/' . $filename . '.' . $im['extension']);
                    list($im['width'], $im['height']) = getimagesize($imageFile);

                    try {
                        $file = $imageFile->move($im['location'], $im['name'] . '.' . $im['extension']);
                        $newImage = new self();

                        // file moved, save
                        $newImage->fill($im);
                        if ($newImage->save()) {
                            $result->extra = $newImage;
                            $result->success = true;
                            $result->error = null;
                        }
                    } catch (Exception $e) {
                        $result->error = $e->getMessage();
                        $result->message = null;
                    }
                } else {
                    $result->extra = $existing->first();
                    $result->message = 'Image reused.';
                    $result->success = true;
                    $result->error = null;
                }
            }
        }

        return $result;
    }
}

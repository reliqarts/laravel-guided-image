<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Examples;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReliqArts\GuidedImage\Contracts\GuidedImage;
use ReliqArts\GuidedImage\Concerns\Guided;

/**
 *  Image model.
 *
 * @property Collection $posts
 */
class Image extends Model implements GuidedImage
{
    use Guided;

    /**
     *  Images table.
     */
    protected $table = 'images';

    /**
     *  Set guard.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     *  Posts.
     */
    public function posts()
    {
        return $this->morphedByMany('App\Post', 'imageable');
    }

    /**
     * {@inheritdoc}
     */
    public function isSafeForDelete(int $safeAmount = 1): bool
    {
        /** @noinspection PhpUndefinedClassInspection */
        $posts = Post::withTrashed()->where('image_id', $this->id)->get();
        $posts = $this->posts->merge($posts);
        $usage = $posts->count();

        return $usage <= $safeAmount;
    }
}

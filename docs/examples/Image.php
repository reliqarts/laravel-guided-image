<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ReliQArts\GuidedImage\Traits\Guided as GuidedTrait;
use ReliQArts\GuidedImage\Contracts\Guided as GuidedContract;

/**
 *  Image model.
 */
class Image extends Model implements GuidedContract
{
    use GuidedTrait;

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
    public function isSafeForDelete($safeAmount = 1)
    {
        $posts = Post::withTrashed()->where('image_id', $this->id)->get();
        $posts = $this->posts->merge($posts);
        $usage = $posts->count();

        return $usage <= $safeAmount;
    }
}

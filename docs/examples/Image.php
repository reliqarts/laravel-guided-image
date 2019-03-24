<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ReliqArts\GuidedImage\Contracts\Guided as GuidedContract;
use ReliqArts\GuidedImage\Traits\Guided as GuidedTrait;

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

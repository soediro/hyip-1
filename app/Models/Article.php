<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

/**
 * App\Models\Article
 *
 * @property int $id
 * @property string $title
 * @property string $uri
 * @property string $content
 * @property string|null $photo
 * @property string|null $preview
 * @property int $published
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article published()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article wherePreview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereTypeId($value)
 * @mixin \Eloquent
 * @property int $type_id 1 - новость, 2 - акция
 * @property string|null $lang
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article blog()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article stock()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Article whereLang($value)
 */
class Article extends Model
{
    protected $table = 'articles';

    protected $fillable = [
        'title',
        'uri',
        'content',
        'photo',
        'preview',
        'published',
        'type_id',
        'lang'
    ];


    public function scopePublished()
    {
        return $this->wherePublished(1);
    }

    public function scopeBlog()
    {
        return $this->where(['type_id' => 1, 'published' => 1, 'lang' => Session::get('applocale')]);
    }

    public function scopeStock()
    {
        return $this->where(['type_id' => 2, 'published' => 1, 'lang' => Session::get('applocale')]);
    }
}

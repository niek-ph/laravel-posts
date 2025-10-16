<?php

namespace NiekPH\LaravelPosts\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Resources\Json\JsonResource;
use NiekPH\LaravelPosts\Database\Factories\CommentFactory;
use NiekPH\LaravelPosts\Http\Resources\CommentResource;
use NiekPH\LaravelPosts\LaravelPosts;

/**
 * @property ?int $rating
 * @property string $comment
 * @property bool $is_approved
 * @property string $author_name
 * @property string $author_email
 * @property mixed $post_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ?Post $post
 *
 * @mixin Model
 */
class Comment extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('posts.database.tables.comments', 'comments');
    }

    public function getConnectionName()
    {
        return config('posts.database.connection');
    }

    protected $fillable = [
        'rating',
        'comment',
        'is_approved',
        'author_name',
        'author_email',
        'post_id',
    ];

    protected $attributes = [
        'is_approved' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'rating' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(LaravelPosts::$postModel);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    public function toResource(?string $resourceClass = null): JsonResource
    {
        return new CommentResource($this);
    }
}

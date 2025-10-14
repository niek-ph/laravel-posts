<?php

namespace NiekPH\LaravelPosts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \NiekPH\LaravelPosts\Models\Post
 */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'slug' => $this->slug,
            'url' => $this->url,
            'full_path' => $this->full_path,
            'sort_order' => $this->sort_order,
            'body' => $this->body,
            'published_at' => $this->published_at,
            'metadata' => $this->metadata,
            'featured_image' => $this->featured_image,
            'featured_image_url' => $this->featured_image_url,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'author' => new AuthorResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}

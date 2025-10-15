<?php

namespace NiekPH\LaravelPosts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \NiekPH\LaravelPosts\Models\Category
 */
class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'metadata' => $this->metadata,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'depth' => $this->depth,
            'url' => $this->url,
            'full_path' => $this->full_path,
            'featured_image' => $this->featured_image,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'parent_category' => new CategoryResource($this->whenLoaded('parent_category')),
            'child_categories' => CategoryResource::collection($this->whenLoaded('child_categories')),
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}

<?php

namespace NiekPH\LaravelPosts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->collection);
    }
}

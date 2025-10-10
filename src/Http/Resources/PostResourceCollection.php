<?php

namespace NiekPH\LaravelPosts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PostResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): AnonymousResourceCollection
    {
        return PostResource::collection($this->collection);
    }
}

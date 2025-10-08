<?php

namespace NiekPH\LaravelPosts\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NiekPH\LaravelPosts\LaravelPosts
 */
class LaravelPosts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NiekPH\LaravelPosts\LaravelPosts::class;
    }
}

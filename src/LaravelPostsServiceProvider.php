<?php

namespace NiekPH\LaravelPosts;

use NiekPH\LaravelPosts\Commands\LaravelPostsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelPostsServiceProvider extends PackageServiceProvider
{
    public function bootingPackage(): void
    {
        $models = $this->app['config']->get('posts.models');

        LaravelPosts::useAuthorModel($models['author']);
        LaravelPosts::useCategoryModel($models['category']);
        LaravelPosts::useCommentModel($models['comment']);
        LaravelPosts::usePostModel($models['post']);
        LaravelPosts::useTagModel($models['tag']);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-posts')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_posts_table')
            ->hasCommand(LaravelPostsCommand::class);
    }
}

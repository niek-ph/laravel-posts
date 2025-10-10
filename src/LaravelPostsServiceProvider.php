<?php

namespace NiekPH\LaravelPosts;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
            ->hasMigration('create_laravel_posts_table')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('niek-ph/laravel-posts');
            });
    }
}

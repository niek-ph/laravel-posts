<?php

return [
    /**
     * You can optionally override the models.
     */
    'models' => [
        'author' => \NiekPH\LaravelPosts\Models\Author::class,
        'category' => \NiekPH\LaravelPosts\Models\Category::class,
        'comment' => \NiekPH\LaravelPosts\Models\Comment::class,
        'post' => \NiekPH\LaravelPosts\Models\Post::class,
        'tag' => \NiekPH\LaravelPosts\Models\Tag::class,
    ],

    'database' => [
        /**
         * The type of user ID column to create in the migration.
         * Options: 'uuid', 'ulid', 'bigInteger'
         */
        'id_type' => 'bigInteger',

        /**
         * Optional: You can customize the database connection.
         */
        'connection' => null,

        /**
         * You can optionally override the table names.
         */
        'tables' => [
            'authors' => 'authors',
            'categories' => 'categories',
            'comments' => 'comments',
            'posts' => 'posts',
            'post_comments' => 'post_comments',
            'tags' => 'tags',
            'taggables' => 'taggables',
        ],
    ],
];

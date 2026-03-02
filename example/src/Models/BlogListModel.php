<?php
/**
 * Blog List Model
 *
 * Fetches all blog posts for listing view.
 * Demonstrates simple data fetching in Models.
 */

declare(strict_types=1);

namespace BlogExample\Models;

use PageMill\MVC\ModelAbstract;

class BlogListModel extends ModelAbstract {

    /**
     * Fetch all blog posts
     *
     * In a real application, this would query a database.
     *
     * @return array<string, mixed>
     */
    public function getData(): array {
        return [
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'Getting Started with PageMill MVC',
                    'slug' => 'getting-started',
                    'author' => 'Jane Developer',
                    'date' => '2026-03-01',
                    'excerpt' => 'Learn how to build modern PHP applications with PageMill MVC framework.',
                    'tags' => ['php', 'mvc', 'tutorial']
                ],
                [
                    'id' => 2,
                    'title' => 'Asset Management Best Practices',
                    'slug' => 'asset-management',
                    'author' => 'John Engineer',
                    'date' => '2026-03-02',
                    'excerpt' => 'Optimize your CSS and JavaScript loading with PageMill\'s asset system.',
                    'tags' => ['assets', 'performance', 'best-practices']
                ],
                [
                    'id' => 3,
                    'title' => 'Building RESTful APIs',
                    'slug' => 'restful-apis',
                    'author' => 'Jane Developer',
                    'date' => '2026-03-03',
                    'excerpt' => 'Create clean JSON APIs using content negotiation.',
                    'tags' => ['api', 'json', 'rest']
                ]
            ],
            'total' => 3
        ];
    }
}

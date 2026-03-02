<?php
/**
 * Blog Post Model
 *
 * Fetches a single blog post by ID.
 * Demonstrates parameterized models.
 */

declare(strict_types=1);

namespace BlogExample\Models;

use PageMill\MVC\ModelAbstract;

class BlogPostModel extends ModelAbstract {

    /**
     * Post ID
     *
     * @var int
     */
    public int $id = 0;

    /**
     * Fetch single blog post
     *
     * In a real application, this would query a database.
     *
     * @return array<string, mixed>
     */
    public function getData(): array {
        $all_posts = [
            1 => [
                'id' => 1,
                'title' => 'Getting Started with PageMill MVC',
                'slug' => 'getting-started',
                'author' => 'Jane Developer',
                'date' => '2026-03-01',
                'excerpt' => 'Learn how to build modern PHP applications with PageMill MVC framework.',
                'content' => "PageMill MVC is a lightweight framework that provides just what you need to build modern PHP applications.\n\nKey features include:\n\n- Full MVC architecture\n- Asset management\n- Content negotiation\n- Type-safe property mapping\n\nThis tutorial will walk you through building your first application.",
                'tags' => ['php', 'mvc', 'tutorial']
            ],
            2 => [
                'id' => 2,
                'title' => 'Asset Management Best Practices',
                'slug' => 'asset-management',
                'author' => 'John Engineer',
                'date' => '2026-03-02',
                'excerpt' => 'Optimize your CSS and JavaScript loading with PageMill\'s asset system.',
                'content' => "The asset management system in PageMill MVC allows you to:\n\n- Organize assets by type and group\n- Combine multiple files\n- Add cache busting\n- Auto-load component assets\n\nLearn how to optimize your application's asset loading.",
                'tags' => ['assets', 'performance', 'best-practices']
            ],
            3 => [
                'id' => 3,
                'title' => 'Building RESTful APIs',
                'slug' => 'restful-apis',
                'author' => 'Jane Developer',
                'date' => '2026-03-03',
                'excerpt' => 'Create clean JSON APIs using content negotiation.',
                'content' => "PageMill MVC makes it easy to support both HTML and JSON responses from the same controller.\n\nUsing content negotiation, your application can automatically detect the requested format and respond appropriately.\n\nThis guide shows you how to build a RESTful API alongside your HTML interface.",
                'tags' => ['api', 'json', 'rest']
            ]
        ];

        $post = $all_posts[$this->id] ?? null;

        return [
            'post' => $post
        ];
    }
}

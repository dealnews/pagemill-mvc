<?php
/**
 * Validate Post Exists Action
 *
 * Checks if the requested blog post ID exists.
 * Demonstrates validation logic in Actions.
 */

declare(strict_types=1);

namespace BlogExample\Actions;

use PageMill\MVC\ActionAbstract;

class ValidatePostExistsAction extends ActionAbstract {
    
    /**
     * Post ID to validate
     *
     * @var int
     */
    public int $id = 0;
    
    /**
     * Execute the validation
     *
     * Checks if post exists and returns it, or adds error.
     *
     * @param array<string, mixed> $data Existing data
     * @return mixed Post data if valid, null if not
     */
    protected function doAction(array $data): mixed {
        // Get all posts (in real app, this would be a database query)
        $posts = $this->getAllPosts();
        
        // Find the requested post
        $post = null;
        foreach ($posts as $p) {
            if ($p['id'] === $this->id) {
                $post = $p;
                break;
            }
        }
        
        if (!$post) {
            $this->errors[] = "Blog post with ID {$this->id} not found";
            return null;
        }
        
        return ['post' => $post];
    }
    
    /**
     * Get all posts (demo data)
     *
     * In a real application, this would query a database.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getAllPosts(): array {
        return [
            [
                'id' => 1,
                'title' => 'Getting Started with PageMill MVC',
                'slug' => 'getting-started',
                'author' => 'Jane Developer',
                'date' => '2026-03-01',
                'excerpt' => 'Learn how to build modern PHP applications with PageMill MVC framework.',
                'content' => 'PageMill MVC is a lightweight framework that provides just what you need...',
                'tags' => ['php', 'mvc', 'tutorial']
            ],
            [
                'id' => 2,
                'title' => 'Asset Management Best Practices',
                'slug' => 'asset-management',
                'author' => 'John Engineer',
                'date' => '2026-03-02',
                'excerpt' => 'Optimize your CSS and JavaScript loading with PageMill\'s asset system.',
                'content' => 'The asset management system in PageMill MVC allows you to...',
                'tags' => ['assets', 'performance', 'best-practices']
            ],
            [
                'id' => 3,
                'title' => 'Building RESTful APIs',
                'slug' => 'restful-apis',
                'author' => 'Jane Developer',
                'date' => '2026-03-03',
                'excerpt' => 'Create clean JSON APIs using content negotiation.',
                'content' => 'PageMill MVC makes it easy to support both HTML and JSON responses...',
                'tags' => ['api', 'json', 'rest']
            ]
        ];
    }
}

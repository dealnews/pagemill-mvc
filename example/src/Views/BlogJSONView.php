<?php
/**
 * Blog JSON View
 *
 * Returns blog data as JSON.
 * Demonstrates API responses with JSONAbstract.
 */

declare(strict_types=1);

namespace BlogExample\Views;

use PageMill\MVC\View\JSONAbstract;

class BlogJSONView extends JSONAbstract {
    
    /**
     * Blog posts array (for list)
     *
     * @var array<int, array<string, mixed>>
     */
    public array $posts = [];
    
    /**
     * Single post (for detail)
     *
     * @var array<string, mixed>|null
     */
    public ?array $post = null;
    
    /**
     * Total count
     *
     * @var int
     */
    public int $total = 0;
    
    /**
     * Errors from validation
     *
     * @var array<int, string>
     */
    public array $_errors = [];
    
    /**
     * Get data to encode as JSON
     *
     * @return array<string, mixed>
     */
    protected function getData(): array {
        // Error response
        if (!empty($this->_errors)) {
            return [
                'status' => 'error',
                'errors' => $this->_errors
            ];
        }
        
        // Single post response
        if ($this->post !== null) {
            return [
                'status' => 'success',
                'data' => $this->post
            ];
        }
        
        // List response
        return [
            'status' => 'success',
            'data' => [
                'posts' => $this->posts,
                'total' => $this->total
            ]
        ];
    }
}

<?php
/**
 * Blog Controller
 *
 * Handles blog listing and single post views.
 * Demonstrates input filtering, validation actions, and model building.
 */

declare(strict_types=1);

namespace BlogExample\Controllers;

use BlogExample\Actions\ValidatePostExistsAction;
use BlogExample\Models\BlogListModel;
use BlogExample\Models\BlogPostModel;
use BlogExample\Responders\BlogResponder;
use PageMill\MVC\ControllerAbstract;

class BlogController extends ControllerAbstract {
    
    /**
     * Define input filters
     *
     * Validates and sanitizes the 'id' parameter if present.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getFilters(): array {
        return [
            INPUT_GET => [
                'id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_NULL_ON_FAILURE,
                    'options' => ['min_range' => 1],
                    'default' => null
                ]
            ]
        ];
    }
    
    /**
     * Get request actions (validation)
     *
     * If an ID is provided, validate that the post exists.
     *
     * @return array<int, class-string>
     */
    protected function getRequestActions(): array {
        // Only validate if ID is provided
        if (!empty($this->inputs['id'])) {
            return [ValidatePostExistsAction::class];
        }
        
        return [];
    }
    
    /**
     * Get data actions (none needed for this example)
     *
     * @return array<int, class-string>
     */
    protected function getDataActions(): array {
        return [];
    }
    
    /**
     * Get models to build for data fetching
     *
     * If ID is provided and valid, load single post.
     * Otherwise, load all posts.
     *
     * @return array<int, class-string>
     */
    protected function getModels(): array {
        // Check if validation failed
        if (!empty($this->inputs['_errors'])) {
            return [];
        }
        
        // Single post view
        if (!empty($this->inputs['id'])) {
            return [
                BlogPostModel::class
            ];
        }
        
        // List view
        return [
            BlogListModel::class
        ];
    }
    
    /**
     * Get the responder for this controller
     *
     * @return \PageMill\MVC\ResponderAbstract
     */
    protected function getResponder(): \PageMill\MVC\ResponderAbstract {
        return new BlogResponder();
    }
}

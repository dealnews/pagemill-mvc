<?php
/**
 * Blog Responder
 *
 * Handles content negotiation between HTML and JSON.
 * Demonstrates how one controller can serve multiple formats.
 */

declare(strict_types=1);

namespace BlogExample\Responders;

use BlogExample\Views\BlogJSONView;
use BlogExample\Views\BlogListView;
use BlogExample\Views\BlogPostView;
use PageMill\MVC\ResponderAbstract;

class BlogResponder extends ResponderAbstract {
    
    /**
     * Define accepted content types
     *
     * @return array<int, string>
     */
    protected function getAcceptedContentTypes(): array {
        return [
            'text/html',
            'application/json'
        ];
    }
    
    /**
     * Choose view based on content type and data
     *
     * @param array<string, mixed> $data Data from models and actions
     * @param array<string, mixed> $inputs Filtered request inputs
     * @return string View class name
     */
    protected function getView(array $data, array $inputs): string {
        // JSON view for API requests
        if ($this->content_type === 'application/json') {
            return BlogJSONView::class;
        }
        
        // HTML views based on data
        // If we have a single post, use post view
        if (!empty($data['post'])) {
            return BlogPostView::class;
        }
        
        // Otherwise, list view
        return BlogListView::class;
    }
}

<?php
/**
 * Blog Post View
 *
 * Displays a single blog post.
 * Demonstrates detailed view with metadata.
 */

declare(strict_types=1);

namespace BlogExample\Views;

use PageMill\MVC\Template\HTMLAbstract;

class BlogPostView extends HTMLAbstract {
    
    /**
     * Blog post data
     *
     * @var array<string, mixed>|null
     */
    public ?array $post = null;
    
    /**
     * Prepare document metadata and assets
     *
     * @return void
     */
    protected function prepareDocument(): void {
        if ($this->post) {
            $this->document->title = $this->post['title'] . ' - PageMill MVC Blog';
            $this->document->canonical = 'https://example.com/post/' . $this->post['slug'];
            $this->document->addMeta([
                'name' => 'description',
                'content' => $this->post['excerpt']
            ]);
            $this->document->addMeta([
                'name' => 'author',
                'content' => $this->post['author']
            ]);
        }
        
        // Add CSS assets
        $this->assets->addLocation('css', [
            'directory' => __DIR__ . '/../../assets/css',
            'url' => '/assets/css'
        ]);
        $this->assets->add('css', ['blog', 'post']);
    }
    
    /**
     * Generate HTML header
     *
     * @return void
     */
    protected function generateHeader(): void {
        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $this->document->generateHead();
        $this->assets->link(null, 'css');
        echo '</head>';
        echo '<body>';
        echo '<header class="site-header">';
        echo '<h1>PageMill MVC Blog</h1>';
        echo '<nav>';
        echo '<a href="/">← Back to Home</a>';
        echo '</nav>';
        echo '</header>';
        echo '<main>';
    }
    
    /**
     * Generate main content
     *
     * @return void
     */
    protected function generateBody(): void {
        if (!$this->post) {
            echo '<div class="error">';
            echo '<h2>Post Not Found</h2>';
            echo '<p>The requested blog post could not be found.</p>';
            echo '<a href="/">Return to blog list</a>';
            echo '</div>';
            return;
        }
        
        echo '<article class="blog-post">';
        echo '<header>';
        echo '<h2>' . htmlspecialchars($this->post['title']) . '</h2>';
        echo '<div class="post-meta">';
        echo '<span class="author">By ' . htmlspecialchars($this->post['author']) . '</span>';
        echo ' | ';
        echo '<time datetime="' . $this->post['date'] . '">' . $this->post['date'] . '</time>';
        echo '</div>';
        echo '</header>';
        
        echo '<div class="post-content">';
        echo nl2br(htmlspecialchars($this->post['content']));
        echo '</div>';
        
        if (!empty($this->post['tags'])) {
            echo '<footer class="post-tags">';
            echo '<strong>Tags:</strong> ';
            foreach ($this->post['tags'] as $tag) {
                echo '<span class="tag">' . htmlspecialchars($tag) . '</span> ';
            }
            echo '</footer>';
        }
        
        echo '</article>';
    }
    
    /**
     * Generate footer
     *
     * @return void
     */
    protected function generateFooter(): void {
        echo '</main>';
        echo '<footer class="site-footer">';
        echo '<p>&copy; 2026 PageMill MVC Example</p>';
        echo '</footer>';
        echo '</body>';
        echo '</html>';
    }
}

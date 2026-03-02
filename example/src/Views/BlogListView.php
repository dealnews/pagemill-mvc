<?php
/**
 * Blog List View
 *
 * Displays a list of blog posts.
 * Demonstrates HTML template with assets and components.
 */

declare(strict_types=1);

namespace BlogExample\Views;

use BlogExample\Elements\PostCardElement;
use PageMill\MVC\Template\HTMLAbstract;

class BlogListView extends HTMLAbstract {

    /**
     * Blog posts array
     *
     * @var array<int, array<string, mixed>>
     */
    public array $posts = [];

    /**
     * Total count
     *
     * @var int
     */
    public int $total = 0;

    /**
     * Prepare document metadata and assets
     *
     * @return void
     */
    protected function prepareDocument(): void {
        $this->document->title = 'Blog - PageMill MVC Example';
        $this->document->addMeta([
            'name' => 'description',
            'content' => 'Example blog built with PageMill MVC framework'
        ]);

        // Add CSS assets
        $this->assets->addLocation('css', [
            'directory' => __DIR__ . '/../../assets/css',
            'url' => '/assets/css'
        ]);
        $this->assets->add('css', ['blog']);

        // Load post card component assets
        $this->element_assets->add([PostCardElement::class]);
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
        $this->assets->link('css');
        echo '</head>';
        echo '<body>';
        echo '<header class="site-header">';
        echo '<h1>PageMill MVC Blog</h1>';
        echo '<nav>';
        echo '<a href="/">Home</a> | ';
        echo '<a href="/api.php">API</a>';
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
        echo '<div class="blog-list">';
        echo '<h2>Recent Posts (' . $this->total . ')</h2>';

        if (empty($this->posts)) {
            echo '<p>No posts yet.</p>';
        } else {
            echo '<div class="posts-grid">';
            foreach ($this->posts as $post) {
                $card = new PostCardElement($post);
                $card->generateElement();
            }
            echo '</div>';
        }

        echo '</div>';
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

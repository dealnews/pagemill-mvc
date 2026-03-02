<?php
/**
 * Post Card Element
 *
 * Reusable blog post card component.
 * Demonstrates UI elements with automatic asset loading.
 */

declare(strict_types=1);

namespace BlogExample\Elements;

use PageMill\MVC\ElementAbstract;

class PostCardElement extends ElementAbstract {
    
    /**
     * CSS files required by this element
     *
     * Automatically loaded when element is registered via
     * $this->element_assets->add([PostCardElement::class])
     *
     * @var array<string, array<int, string>>
     */
    public static array $assets = [
        'css' => ['post-card']
    ];
    
    /**
     * Post ID
     *
     * @var int
     */
    public int $id = 0;
    
    /**
     * Post title
     *
     * @var string
     */
    public string $title = '';
    
    /**
     * Post excerpt
     *
     * @var string
     */
    public string $excerpt = '';
    
    /**
     * Post author
     *
     * @var string
     */
    public string $author = '';
    
    /**
     * Post date
     *
     * @var string
     */
    public string $date = '';
    
    /**
     * Post tags
     *
     * @var array<int, string>
     */
    public array $tags = [];
    
    /**
     * Generate the element HTML
     *
     * @return void
     */
    public function generateElement(): void {
        echo '<article class="post-card">';
        echo '<h3>';
        echo '<a href="/?id=' . $this->id . '">';
        echo htmlspecialchars($this->title);
        echo '</a>';
        echo '</h3>';
        echo '<div class="post-card-meta">';
        echo '<span class="author">' . htmlspecialchars($this->author) . '</span>';
        echo ' • ';
        echo '<time>' . htmlspecialchars($this->date) . '</time>';
        echo '</div>';
        echo '<p class="excerpt">' . htmlspecialchars($this->excerpt) . '</p>';
        
        if (!empty($this->tags)) {
            echo '<div class="tags">';
            foreach ($this->tags as $tag) {
                echo '<span class="tag">' . htmlspecialchars($tag) . '</span> ';
            }
            echo '</div>';
        }
        
        echo '<a href="/?id=' . $this->id . '" class="read-more">Read more →</a>';
        echo '</article>';
    }
}

<?php

declare(strict_types=1);

namespace PageMill\MVC\Tests;

use PageMill\MVC\HTMLElement;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for HTMLElement
 *
 * Tests HTML element attribute handling, tag generation, and XSS protection.
 */
class HTMLElementTest extends TestCase {

    /**
     * Tests constructor accepts and maps attributes
     */
    public function testConstructorMapsAttributes(): void {
        $element = new ConcreteHTMLElement([
            'id' => 'test-id',
            'class' => 'test-class',
            'title' => 'Test Title'
        ]);

        $this->assertEquals('test-id', $element->getId());
        $this->assertEquals('test-class', $element->getClass());
        $this->assertEquals('Test Title', $element->getTitle());
    }

    /**
     * Tests constructor with empty attributes
     */
    public function testConstructorWithEmptyAttributes(): void {
        $element = new ConcreteHTMLElement([]);

        $this->assertInstanceOf(HTMLElement::class, $element);
    }

    /**
     * Tests open method generates opening tag with attributes
     */
    public function testOpenGeneratesOpeningTag(): void {
        $element = new ConcreteHTMLElement(['id' => 'my-div', 'class' => 'container']);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('<div', $output);
        $this->assertStringContainsString('id="my-div"', $output);
        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('>', $output);
    }

    /**
     * Tests open method without attributes
     */
    public function testOpenWithoutAttributes(): void {
        $element = new ConcreteHTMLElement([]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertEquals('<div>', $output);
    }

    /**
     * Tests close method generates closing tag
     */
    public function testCloseGeneratesClosingTag(): void {
        $element = new ConcreteHTMLElement([]);

        ob_start();
        $element->close('div');
        $output = ob_get_clean();

        $this->assertEquals('</div>', $output);
    }

    /**
     * Tests boolean attributes render as name-only
     */
    public function testBooleanAttributesRenderAsNameOnly(): void {
        $element = new ConcreteHTMLElement(['hidden' => true, 'contenteditable' => true]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('hidden', $output);
        $this->assertStringContainsString('contenteditable', $output);
        $this->assertStringNotContainsString('hidden="true"', $output);
    }

    /**
     * Tests boolean false attributes are omitted
     */
    public function testBooleanFalseAttributesOmitted(): void {
        $element = new ConcreteHTMLElement(['hidden' => false]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('hidden', $output);
    }

    /**
     * Tests null attributes are omitted
     */
    public function testNullAttributesOmitted(): void {
        $element = new ConcreteHTMLElement(['title' => null]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('title', $output);
    }

    /**
     * Tests data attributes render with data- prefix
     */
    public function testDataAttributesRenderWithPrefix(): void {
        $element = new ConcreteHTMLElement([
            'data' => [
                'user-id' => 123,
                'action' => 'click'
            ]
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('data-user-id="123"', $output);
        $this->assertStringContainsString('data-action="click"', $output);
    }

    /**
     * Tests data attributes with boolean values convert to string
     */
    public function testDataAttributesBooleanConversion(): void {
        $element = new ConcreteHTMLElement([
            'data' => [
                'enabled' => true,
                'disabled' => false
            ]
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('data-enabled="true"', $output);
        $this->assertStringContainsString('data-disabled="false"', $output);
    }

    /**
     * Tests meta_attributes for custom attributes
     */
    public function testMetaAttributesRender(): void {
        $element = new ConcreteHTMLElement([
            'meta_attributes' => [
                'v-if' => 'isVisible',
                'ng-model' => 'userName'
            ]
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('v-if="isVisible"', $output);
        $this->assertStringContainsString('ng-model="userName"', $output);
    }

    /**
     * Tests attribute values are escaped for XSS protection
     */
    public function testAttributeValuesEscaped(): void {
        $element = new ConcreteHTMLElement([
            'title' => 'Test <script>alert("xss")</script>',
            'class' => 'class"onclick="alert(1)"'
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        // Check that dangerous characters are escaped
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringContainsString('&quot;', $output);
        
        // Check that the original dangerous content is not present unescaped
        $this->assertStringNotContainsString('<script>', $output);
        
        // The onclick is present but safely escaped as &quot;onclick=&quot;
        // which is safe because it's inside an already-quoted attribute value
        $this->assertStringContainsString('class&quot;onclick=&quot;', $output);
    }

    /**
     * Tests all HTML5 global attributes
     */
    public function testAllGlobalAttributes(): void {
        $element = new ConcreteHTMLElement([
            'accesskey' => 'a',
            'class' => 'test',
            'contenteditable' => true,
            'dir' => 'ltr',
            'draggable' => true,
            'hidden' => true,
            'id' => 'test-id',
            'lang' => 'en',
            'spellcheck' => true,
            'tabindex' => 1,
            'title' => 'Test'
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('accesskey="a"', $output);
        $this->assertStringContainsString('class="test"', $output);
        $this->assertStringContainsString('contenteditable', $output);
        $this->assertStringContainsString('dir="ltr"', $output);
        $this->assertStringContainsString('draggable', $output);
        $this->assertStringContainsString('hidden', $output);
        $this->assertStringContainsString('id="test-id"', $output);
        $this->assertStringContainsString('lang="en"', $output);
        $this->assertStringContainsString('spellcheck', $output);
        $this->assertStringContainsString('tabindex="1"', $output);
        $this->assertStringContainsString('title="Test"', $output);
    }

    /**
     * Tests microdata attributes
     */
    public function testMicrodataAttributes(): void {
        $element = new ConcreteHTMLElement([
            'itemscope' => 'itemscope',
            'itemtype' => 'https://schema.org/Person',
            'itemprop' => 'name',
            'itemid' => 'person-123'
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('itemscope="itemscope"', $output);
        $this->assertStringContainsString('itemtype="https://schema.org/Person"', $output);
        $this->assertStringContainsString('itemprop="name"', $output);
        $this->assertStringContainsString('itemid="person-123"', $output);
    }

    /**
     * Tests tabindex with different values
     */
    public function testTabindexValues(): void {
        $element = new ConcreteHTMLElement(['tabindex' => -1]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('tabindex="-1"', $output);
    }

    /**
     * Tests translate attribute with different values
     */
    public function testTranslateAttribute(): void {
        $element = new ConcreteHTMLElement(['translate' => 'no']);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('translate="no"', $output);
    }

    /**
     * Tests array values in data attributes are JSON encoded
     */
    public function testDataAttributeArrayValues(): void {
        $element = new ConcreteHTMLElement([
            'data' => [
                'config' => ['x' => 1, 'y' => 2]
            ]
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('data-config=', $output);
        $this->assertStringContainsString('&quot;x&quot;', $output);
        $this->assertStringContainsString('&quot;y&quot;', $output);
    }

    /**
     * Tests open with additional child class attributes
     */
    public function testOpenWithAdditionalAttributes(): void {
        $element = new ExtendedHTMLElement([
            'id' => 'test',
            'custom_attr' => 'custom-value'
        ]);

        ob_start();
        $element->open('div', ['custom_attr']);
        $output = ob_get_clean();

        $this->assertStringContainsString('id="test"', $output);
        $this->assertStringContainsString('custom_attr="custom-value"', $output);
    }

    /**
     * Tests multiple spaces in attribute values are preserved
     */
    public function testMultipleSpacesPreserved(): void {
        $element = new ConcreteHTMLElement(['class' => 'class1  class2   class3']);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('class="class1  class2   class3"', $output);
    }

    /**
     * Tests empty string attributes render
     */
    public function testEmptyStringAttributesRender(): void {
        $element = new ConcreteHTMLElement(['title' => '']);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('title=""', $output);
    }

    /**
     * Tests numeric zero attributes render
     */
    public function testNumericZeroAttributesRender(): void {
        $element = new ConcreteHTMLElement(['tabindex' => 0]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('tabindex="0"', $output);
    }

    /**
     * Tests slot attribute
     */
    public function testSlotAttribute(): void {
        $element = new ConcreteHTMLElement(['slot' => 'header-slot']);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('slot="header-slot"', $output);
    }

    /**
     * Tests is attribute for custom elements
     */
    public function testIsAttribute(): void {
        $element = new ConcreteHTMLElement(['is' => 'my-button']);

        ob_start();
        $element->open('button');
        $output = ob_get_clean();

        $this->assertStringContainsString('is="my-button"', $output);
    }

    /**
     * Tests dropzone attribute
     */
    public function testDropzoneAttribute(): void {
        $element = new ConcreteHTMLElement(['dropzone' => 'copy']);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('dropzone="copy"', $output);
    }

    /**
     * Tests attributes render in consistent order
     */
    public function testAttributeConsistentOrdering(): void {
        $element = new ConcreteHTMLElement([
            'id' => 'test',
            'class' => 'test-class',
            'title' => 'Test'
        ]);

        ob_start();
        $element->open('div');
        $output1 = ob_get_clean();

        ob_start();
        $element->open('div');
        $output2 = ob_get_clean();

        $this->assertEquals($output1, $output2);
    }

    /**
     * Tests special characters in attribute values
     */
    public function testSpecialCharactersInAttributes(): void {
        $element = new ConcreteHTMLElement([
            'title' => 'Test & "quotes" < >'
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('&amp;', $output);
        $this->assertStringContainsString('&quot;', $output);
        $this->assertStringContainsString('&lt;', $output);
        $this->assertStringContainsString('&gt;', $output);
    }

    /**
     * Tests Unicode characters in attributes
     */
    public function testUnicodeCharactersInAttributes(): void {
        $element = new ConcreteHTMLElement([
            'title' => 'Test 中文 émojis 🎉'
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('title="Test 中文 émojis 🎉"', $output);
    }

    /**
     * Tests combining multiple attribute types
     */
    public function testCombiningMultipleAttributeTypes(): void {
        $element = new ConcreteHTMLElement([
            'id' => 'test',
            'class' => 'container',
            'hidden' => true,
            'data' => ['user-id' => 123],
            'meta_attributes' => ['v-if' => 'show']
        ]);

        ob_start();
        $element->open('div');
        $output = ob_get_clean();

        $this->assertStringContainsString('id="test"', $output);
        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('hidden', $output);
        $this->assertStringContainsString('data-user-id="123"', $output);
        $this->assertStringContainsString('v-if="show"', $output);
    }

    /**
     * Tests PropertyMap trait integration
     */
    public function testPropertyMapIntegration(): void {
        // PropertyMap throws exception for unknown properties (strict mode)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration input');

        new ConcreteHTMLElement([
            'id' => 'mapped-id',
            'unknown_property' => 'ignored'
        ]);
    }

    /**
     * Tests PropertyMap with valid properties
     */
    public function testPropertyMapWithValidProperties(): void {
        $element = new ConcreteHTMLElement(['id' => 'mapped-id']);

        $this->assertEquals('mapped-id', $element->getId());
    }

    /**
     * Tests complete tag generation flow
     */
    public function testCompleteTagGeneration(): void {
        $element = new ConcreteHTMLElement(['id' => 'wrapper', 'class' => 'container']);

        ob_start();
        $element->open('div');
        echo 'Content';
        $element->close('div');
        $output = ob_get_clean();

        $this->assertStringStartsWith('<div', $output);
        $this->assertStringContainsString('id="wrapper"', $output);
        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('>Content</div>', $output);
    }
}

/**
 * Concrete implementation of HTMLElement for testing
 */
class ConcreteHTMLElement extends HTMLElement {

    // Expose protected properties for testing
    public function getId(): ?string {
        return $this->id;
    }

    public function getClass(): ?string {
        return $this->class;
    }

    public function getTitle(): ?string {
        return $this->title;
    }
}

/**
 * Extended HTMLElement with custom attributes
 */
class ExtendedHTMLElement extends HTMLElement {

    protected ?string $custom_attr = null;
}

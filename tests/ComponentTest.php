<?php

namespace PatternBuilder\Test;

class ComponentTest extends AbstractTest
{
    /**
     * Tests for the PropertyInterface::set() method.
     */
    public function testSetMethod()
    {
        $text = $this->getComponent('text');
        $this->assertNull($text->get('value'), 'Value is initially null');

        $value = 'http://example.com';
        $text->set('value', $value);
        $this->assertEquals($text->get('value'), $value, 'The value correctly set');
    }

    /**
     * Test readonly and default properties.
     */
    public function testPropertyReadOnlyDefaults()
    {
        $default = $this->getComponent('default_property');
        $this->assertEquals($default->get('name'), 'default_value', 'Default is set');

        $default->set('name', 'somevalue');
        $this->assertEquals($default->get('name'), 'default_value', 'Readonly cannot be changed');
    }

    /**
     * Test setting values on a property that is an object.
     */
    public function testObjectsAsProperties()
    {
        $component = $this->getComponent('object');

        $this->assertNull($component->get('link'), 'Link Property is null.');

        $title_text = 'Link title';
        $url = 'http://example.com';
        $component->set('link', array('title' => $title_text));

        $value = $component->get('link');
        $this->assertEquals($value instanceof \PatternBuilder\Property\Component\Component, true, 'Value is a component');
        $this->assertEquals($value->get('title'), $title_text, 'Title text matches set value');
        $this->assertNull($value->get('url'), 'HREF is null');

        $title_text = $title_text.' and some more content';
        $component->set('link', array('title' => $title_text, 'url' => $url));
        $value2 = $component->get('link');

        $this->assertEquals($value, $value2, 'The two link values are equal');
        $this->assertEquals($value->get('title'), $title_text, 'Title text matches set value');
        $this->assertEquals($value->get('url'), $url, 'The link URL matches the set value');

        /*
        // @todo: this test should probably work.
        $component->set('link', array());
        $value3 = $component->get($link);

        // This probably should not work and can be safely removed.
        $this->assertNull($value3, 'The value is null');

        // These two should almost certainly pass.
        $this->assertNull($value3->get('title'), 'Title is null');
        $this->assertNull($value3->get('url'), 'URL is null');
        */
    }

    /**
     * Test Simple Validation.
     */
    public function testSimpleValidation()
    {
        /*
        // @todo: This test should also probably pass but at this point would
        // require a refactor of the way rendering and validation works, as well
        // as mocking the JsonSchema\Validator and JsonSchema\Resolver classes.

        $text = $this->getComponent('text');
        $this->assertFalse($text->validate());

        */
    }

    /**
     * Test the Component::isEmpty() method.
     */
    public function testIsEmpty()
    {
        $text = $this->getComponent('text');
        $this->assertTrue($text->isEmpty('value'), 'The text value is empty');

        $text->set('value', 'A non empty value');
        $this->assertFalse($text->isEmpty('value'), 'The text value is not empty');

        $text->set('value', null);
        $this->assertTrue($text->isEmpty('value', 'The text value is once again empty'));

        /*
        @todo: This should maybe pass?

        // Instantiate an empty composite component
        $composite = $this->getComponent('composite');

        // Set the empty text component as the composites content.
        $composite->set('content', $text);
        $this->assertTrue($composite->isEmpty('content'), 'The composite has no content');
        */
    }
}

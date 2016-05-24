<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Test;

class ComponentTest extends AbstractTest
{
    /**
     * Check an array of schema tests.
     *
     * @param array $schema_tests An array of schema tests keyed by schema name with values of:
     *                            - An array keyed by test case index with values of:
     *                            - 'type': 'pass' or 'fail'.
     *                            - 'values': An array values to set on the component.
     *                            - 'scenario': Optional. A description of the test case.
     *                            - 'asserter': Optional. An assertion function to use. Defaults to 'assertTrue' for 'pass' cases and 'assertFalse' for 'fail' cases.
     */
    protected function checkSchemaTests(array $schema_tests)
    {
        foreach ($schema_tests as $schema_name => $test_cases) {
            foreach ($test_cases as $case_index => $case) {
                if (array_key_exists('values', $case)) {
                    $type = isset($case['type']) ? $case['type'] : 'pass';
                    if (!empty($case['asserter'])) {
                        $asserter = $case['asserter'];
                    } else {
                        $asserter = $type == 'pass' ? 'assertTrue' : 'assertFalse';
                    }

                    // Build the component.
                    $component = $this->getComponent($schema_name);
                    $this->pbSetComponentValues($component, $case['values']);

                    // Validate the built component.
                    $validation = $component->validate();
                    $is_valid = $validation === true;

                    // Build assertion message.
                    $assert_message = "The schema '{$schema_name}' test case {$case_index} should {$type} validation.";

                    // Append scenario.
                    if (isset($case['scenario'])) {
                        $assert_message .= ' Scenario: '.$case['scenario'];
                    }

                    // Validation errors.
                    if (!$is_valid && is_array($validation)) {
                        $error_messages = array();
                        foreach ($validation as $error_index => $error) {
                            $error_message = array();
                            foreach ($error as $error_prop => $error_value) {
                                if (trim($error_value)) {
                                    $error_prop_label = strtoupper($error_prop);
                                    $error_message[] = "\t{$error_prop_label}: {$error_value}";
                                }
                            }
                            $error_messages[] = "ERROR #{$error_index}:\n".implode("\n", $error_message);
                        }

                        $assert_message .= " \n".implode("\n", $error_messages)."\n";
                    }

                    $this->{$asserter}($is_valid, $assert_message);
                }
            }
        }
    }

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
     * Test setting values on a property that is an array.
     */
    public function testArraysAsProperties()
    {
        $component = $this->getComponent('composite');
        $this->assertNull($component->get('content'), 'Content Property is null.');

        // Set multiple text components.
        $set_text_values = array();
        for ($c = 0; $c < 3; ++$c) {
            $text_component = $this->getComponent('text');
            $text_value = 'test text '.$c;
            $text_component->set('value', $text_value);
            $set_text_values[$c] = $text_value;

            $component->set('content', $text_component);
        }

        $values = $component->get('content');
        $this->assertEquals(is_array($values), true, 'Values is an array');
        $this->assertEquals(count($values), 3, 'Values has 3 items');

        foreach ($values as $i => $value) {
            $this->assertEquals($value instanceof \PatternBuilder\Property\Component\Component, true, "Value {$i} is a component");
            $this->assertEquals($value->get('value'), $set_text_values[$i], 'Value {$i} text string matches the set value');
        }
    }

    /**
     * Test Simple Validation.
     */
    public function testSimpleValidation()
    {
        $object_value = (object) array('v' => 'text');

        $schema_tests = array(
            'text' => array(
                array(
                    'type' => 'pass',
                    'scenario' => 'Text schema with a string value.',
                    'values' => array('value' => 'pass'),
                ),
                array(
                    'type' => 'fail',
                    'scenario' => 'Text schema with a non-string value.',
                    'values' => array('value' => $object_value),
                ),
            ),
            'object' => array(
                array(
                    'type' => 'pass',
                    'scenario' => 'Object schema with a valid link object.',
                    'values' => array(
                        'link' => array(
                            'title' => 'pass',
                            'url' => 'http://google.com',
                        ),
                    ),
                ),
                array(
                    'type' => 'fail',
                    'description' => 'Object schema with a missing url for the link object.',
                    'values' => array(
                        'link' => array(
                            'title' => 'fail missing url',
                        ),
                    ),
                ),
                array(
                    'type' => 'fail',
                    'scenario' => 'Object schema with a non-string title for the link object.',
                    'values' => array(
                        'link' => array(
                            'title' => $object_value,
                            'url' => 'http://google.com',
                        ),
                    ),
                ),
                array(
                    'type' => 'fail',
                    'scenario' => 'Object schema with a non-object link.',
                    'values' => array(
                        'link' => 'text',
                    ),
                ),
            ),
        );

        // Run the tests.
        $this->checkSchemaTests($schema_tests);
    }

    /**
     * Test Array Validation.
     */
    public function testArrayValidation()
    {
        $text_component = $this->getComponent('text');
        $text_component->set('value', 'text value');

        $cta_component = $this->getComponent('cta');
        $cta_component->set('text', 'link text');
        $cta_component->set('href', 'https://www.redhat.com');

        $cta_invalid_component = $this->getComponent('cta');
        $cta_invalid_component->set('text', 'link text');

        $object_component = $this->getComponent('object');
        $object_component->set('link', array(
            'title' => 'Link title',
            'url' => 'http://example.com',
        ));

        $schema_tests = array(
            'composite' => array(
                array(
                    'type' => 'pass',
                    'scenario' => 'Composite schema with content set to a valid text component.',
                    'values' => array('content' => $text_component),
                ),
                array(
                    'type' => 'pass',
                    'scenario' => 'Composite schema with content set to a valid cta component.',
                    'values' => array('content' => $cta_component),
                ),
                array(
                    'type' => 'pass',
                    'scenario' => 'Composite schema with multiple valid content items.',
                    'values' => array('content' => array(
                            $cta_component,
                            $text_component,
                    )),
                ),
                array(
                    'type' => 'fail',
                    'scenario' => 'Composite schema with content set to an invalid cta component.',
                    'values' => array('content' => $cta_invalid_component),
                ),
                array(
                    'type' => 'fail',
                    'scenario' => 'Composite schema with content set to an invalid component.',
                    'values' => array('content' => $object_component),
                ),
                array(
                    'type' => 'fail',
                    'scenario' => 'Composite schema with content set a string instead of a component.',
                    'values' => array('content' => 'text'),
                ),
                array(
                    'type' => 'pass',
                    'scenario' => 'Composite schema with valid single object item.',
                    'values' => array(
                        'objects' => array(
                            'object' => $object_component,
                            'quote' => array(
                                'text' => 'Test quoted text',
                                'author' => 'tester',
                            ),
                        ),
                    ),
                ),
                array(
                    'type' => 'pass',
                    'scenario' => 'Composite schema with valid multiple object items.',
                    'values' => array(
                        'objects' => array(
                            array(
                                'object' => $object_component,
                                'quote' => array(
                                    'text' => 'Test quoted text',
                                    'author' => 'tester',
                                ),
                            ),
                            array(
                                'object' => $object_component,
                                'quote' => array(
                                    'text' => 'Test quoted text 2',
                                    'author' => 'tester 2',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        // Run the tests.
        $this->checkSchemaTests($schema_tests);
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
        $this->assertTrue($text->isEmpty('value'), 'The text value is once again empty');

        // Instantiate an empty composite component
        $composite = $this->getComponent('composite');
        $this->assertTrue($composite->isEmpty('content'), 'The composite initially has no content');

        // Set a non empty text as the content.
        $text->set('value', 'A non empty value');
        $composite->set('content', $text);
        $this->assertFalse($composite->isEmpty('content'), 'The composite has content');

        // Update child component to a null value.
        $text->set('value', null);
        $this->assertTrue($composite->isEmpty('content'), 'The composite content is empty after the child is set to null');

        // Add a new non-empty component.
        $other_text = $this->getComponent('text');
        $other_text->set('value', 'A non empty value');
        $composite->set('content', $other_text);
        $this->assertFalse($composite->isEmpty('content'), 'The composite has content after a non-empty child was added');
    }
}

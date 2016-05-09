<?php

namespace PatternBuilder\Test;

class RenderTest extends AbstractTest
{
    /**
     * Assert values are within the rendered markup.
     *
     * @param string $markup
     *                              The rendered markup.
     * @param array  $assert_values
     *                              An array of values to assert.
     */
    protected function checkMarkupContainsValues($markup, array $assert_values)
    {
        $ignore_case = false;
        foreach ($assert_values as $key => $value) {
            if (is_array($value)) {
                $this->checkMarkupContainsValues($markup, $value);
            } else {
                $message = "The property {$key} value is present in the rendered markup.";
                $this->assertContains($value, $markup, $message, $ignore_case);
            }
        }
    }

    /**
     * Render tests for the simple data types.
     */
    public function testSimpleData()
    {
        $twig = $this->getTwig();

        $values = array(
          'header' => array(
            'title' => 'Header Title',
            'headline' => 'Header Headline',
            'summary' => 'Header summary',
          ),
          'text' => 'test text',
          'text_readonly' => 'this text should not show',
          'text_escaped' => '<p>escape me</p>',
          'text_html' => '<p>my html goes here</p>',
          'boolean_flag' => true,
          'number_integer' => 99,
          'number_number' => 100.99,
        );

        $assert_values = array(
          'header' => array(
            'title' => '"pb-header--title">'.twig_escape_filter($twig, $values['header']['title'], 'html'),
            'headline' => '"pb-header--headline">'.twig_escape_filter($twig, $values['header']['headline'], 'html'),
            'summary' => '"pb-header--summary">'.twig_escape_filter($twig, $values['header']['summary'], 'html'),
          ),
          'text' => '"pb-text">'.twig_escape_filter($twig, $values['text'], 'html'),
          'text_readonly' => '"pb-text-readonly">READONLY',
          'text_escaped' => '"pb-text-escaped">'.twig_escape_filter($twig, $values['text_escaped'], 'html'),
          'text_html' => '"pb-text-html">'.$values['text_html'],
          'boolean_flag' => '"pb-boolean">'.$values['boolean_flag'] ? 'TRUE' : 'FALSE',
          'number_integer' => '"pb-number-integer">'.$values['number_integer'],
          'number_number' => '"pb-number-number">'.$values['number_number'],
        );

        // Create component.
        $component = $this->getComponent('test_render');

        // Set property values.
        $this->pbSetComponentValues($component, $values);

        // Render the component.
        $markup = $component->render();

        // Check assert values.
        $this->checkMarkupContainsValues($markup, $assert_values);
    }

    /**
     * Render tests for arrays.
     */
    public function testArrays()
    {
        $twig = $this->getTwig();

        $values = array(
          'array_of_strings' => array(
            'string 1',
            'string 2',
            'string 3',
          ),
          'array_of_objects' => array(
            array(
              'foo' => 'foo 1',
              'bar' => 11.222,
              'baz' => false,
            ),
            array(
              'foo' => 'foo 2',
              'bar' => 33.4,
            ),
            array(
              'bar' => 5,
              'baz' => true,
            ),
          ),
        );

        $assert_values = array();
        foreach ($values['array_of_strings'] as $i => $string_item) {
            $assert_values['array_of_strings'][$i] = '"pb-array-of-strings--item--'.$i.'">'.twig_escape_filter($twig, $string_item, 'html');
        }

        foreach ($values['array_of_objects'] as $i => $object_item) {
            foreach ($object_item as $item_key => $item_value) {
                if ($item_key == 'baz') {
                    $assert_value = $item_value ? 'TRUE' : 'FALSE';
                } else {
                    $assert_value = twig_escape_filter($twig, $item_value, 'html');
                }

                $assert_values['array_of_objects'][$i][$item_key] = '"pb-array-with-objects--item--'.$i.'--'.$item_key.'">'.$assert_value;
            }
        }

        // Create component.
        $component = $this->getComponent('test_render');

        // Set property values.
        $this->pbSetComponentValues($component, $values);

        // Render the component.
        $markup = $component->render();

        // Check assert values.
        $this->checkMarkupContainsValues($markup, $assert_values);
    }

    /**
     * Render tests for schema references.
     */
    public function testSchemaReference()
    {
        $cta = $this->getComponent('cta');
        $cta->set('type', 'primary');
        $cta->set('href', 'https://www.redhat.com');
        $cta->set('text', 'CTA Text');
        $cta->set('title', 'CTA Title attribute');

        $image = $this->getComponent('image');
        $image->set('src', 'https://www.redhat.com/profiles/rh/themes/redhatdotcom/img/logo.png');
        $image->set('alt', 'Red Hat');

        $youtube = $this->getComponent('youtube');
        $youtube->set('src', 'https://www.youtube.com/embed/8X1eao17Lok');

        $values = array(
          'cta' => $cta,
          'image' => $image,
          'youtube' => $youtube,
        );

        $assert_values = array(
          'cta' => '<a class="pb-cta" data-cta-type="'.$cta->get('type').'" href="'.$cta->get('href').'" title="'.$cta->get('title').'">'.$cta->get('text'),
          'image' => '<img class="pb-image" src="'.$image->get('src').'" alt="'.$image->get('alt').'" />',
          'youtube' => 'src="'.$youtube->get('src').'"',
        );

        // Create component.
        $component = $this->getComponent('test_render');

        // Set property values.
        $this->pbSetComponentValues($component, $values);

        // Render the component.
        $markup = $component->render();

        // Check assert values.
        $this->checkMarkupContainsValues($markup, $assert_values);
    }
}

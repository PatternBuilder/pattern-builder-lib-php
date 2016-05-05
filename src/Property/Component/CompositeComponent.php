<?php

namespace PatternBuilder\Property\Component;

use PatternBuilder\Property\PropertyInterface;

/**
 * Class to load a schema object.
 */
class CompositeComponent extends Component implements PropertyInterface
{
    /**
     * {@inheritdoc}
     *
     * @param object                   $schema      A parsed json schema definition.
     * @param \Psr\Log\LoggerInterface $logger      Static class to call when returning log messages.
     * @param \Twig_Environment        $twig        Twig object.
     * @param string                   $schema_name Short name of the schema.
     */
    public function __construct($schema, $configuration, $schema_name = null)
    {
        parent::__construct($schema, $configuration, $schema_name);
        $this->property_values = array();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $property_name The property to get the current value for.
     *
     * @return mixed The properties current value. Null if the property does not exist.
     */
    public function get($property_name = null)
    {
        if (isset($this->property_values)) {
            return $this->property_values;
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function set($property_name, $value)
    {
        $this->property_values[] = $value;
    }

    /**
     * Prepare this object for rendering.
     */
    public function prepareRender()
    {
        $template_variables = array();
        foreach ($this->property_values as $property_name => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'prepareRender')) {
                    $template_variables[$property_name] = $value->prepareRender();
                }
            } elseif (is_scalar($value)) {
                $template_variables[$property_name] = $value;
            }
        }

        return $template_variables;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $result = array();
        foreach ($this->property_values as $property_name => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'render')) {
                    $template_variables[$property_name] = $value->render();
                }
            } elseif (is_scalar($value)) {
                $template_variables[$property_name] = $value;
            }
        }

        return $result ? implode('', $result) : '';
    }

    /**
     * Return the type of items this composite should hold.
     */
    protected function type()
    {
        return $this->schema->items->type;
    }
}

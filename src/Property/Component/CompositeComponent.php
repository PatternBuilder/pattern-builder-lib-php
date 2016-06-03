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
     */
    public function initProperties()
    {
        $this->property_values = array();
    }

    /**
     * {@inheritdoc}
     */
    public function initDefaultProperties()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function schemaPropertyExists($property_name)
    {
        return !empty($this->schema->properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaProperty($property_name)
    {
        return empty($this->schema->properties) ? null : $this->schema->properties;
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
     * {@inheritdoc}
     */
    public function render()
    {
        $rendered = '';
        $renders = $this->invoke('render');
        if ($renders) {
            foreach ($renders as $render) {
                if (is_scalar($render)) {
                    $rendered .= $render;
                }
            }
        }

        return $rendered;
    }

    /**
     * Return the type of items this composite should hold.
     */
    protected function type()
    {
        return $this->schema->items->type;
    }
}

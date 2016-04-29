<?php

namespace PatternBuilder\Property;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use PatternBuilder\Configuration\Configuration;

class LeafProperty extends PropertyAbstract implements PropertyInterface, LoggerAwareInterface
{
    protected $property_value;
    protected $schema;

    /**
     * Constructor for the component.
     *
     * @param object $schema A parsed json schema definition.
     * @param Configuration $configuration Config object.
     */
    public function __construct($schema, Configuration $configuration)
    {
        $this->schema = $schema;
        $this->setLogger($configuration->getLogger());

        // Set a default if it exists.
        if (!empty($this->schema->default)) {
            $this->property_value = $this->schema->default;
        }
    }

    /**
     * {@inheritdoc}
     *
     * There is sub properties on leafs.
     */
    public function get($property_name = NULL)
    {
        return $this->property_value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($property_name, $value)
    {
        if (empty($this->schema->readonly)) {
            $this->property_value = $value;
        } else {
            $this->logger->notice('Cannot change the value of the readonly property: %property.', array('%property' => $property_name));
        }
    }

    public function prepareRender()
    {
        return $this->get();
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this->property_value;
    }

}

<?php

namespace PatternBuilder\Property;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use PatternBuilder\Configuration\Configuration;

class LeafProperty implements PropertyInterface, LoggerAwareInterface
{
    protected $property_value;
    protected $schema;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

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
     * Sets a logger instance on the object.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function get($property_name)
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
        return $this->get(false);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this->property_value;
    }
}

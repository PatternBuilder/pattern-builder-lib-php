<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Property;

use Psr\Log\LoggerAwareInterface;

class LeafProperty extends PropertyAbstract implements PropertyInterface, LoggerAwareInterface
{
    protected $property_value;

    /**
     * Initialize the property.
     */
    public function initProperties()
    {
        $this->property_values = null;
        $this->initDefaultProperties();
    }

    /**
     * Instantiate the property default value.
     */
    public function initDefaultProperties()
    {
        if (isset($this->schema->default)) {
            $this->property_value = $this->schema->default;
        }
    }

    /**
     * {@inheritdoc}
     *
     * There is sub properties on leafs.
     */
    public function get($property_name = null)
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

    /**
     * {@inheritdoc}
     */
    public function values()
    {
        return $this->get();
    }

    /**
     * {@inheritdoc}
     */
    public function prepareRender()
    {
        return $this->get();
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this->get();
    }
}

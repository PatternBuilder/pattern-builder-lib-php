<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Property;

interface PropertyInterface
{
    /**
     * Initialize the property values with empty or default values.
     */
    public function initProperties();

    /**
     * Returns all of the stored property values.
     *
     * @return mixed
     */
    public function value();

    /**
     * Gets the set value.
     *
     * @param string $property_name
     *                              Name of the value to get.
     *
     * @return mixed
     */
    public function get($property_name = null);

    /**
     * Sets the value.
     *
     * @param string $property_name
     *                              Name of the value to be set.
     * @param mixed  $value
     *                              Value to be stored.
     *
     * @return mixed
     */
    public function set($property_name, $value);

    /**
     * Render the set values.
     *
     * @return mixed
     */
    public function render();

    /**
     * Prepare this object for rendering.
     *
     * @return mixed
     */
    public function prepareRender();

    /**
     * Determine if a given property contains data.
     *
     * @param string $property_name The property to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public function isEmpty($property_name = null);

    /**
     * Return an instance of the component factory for use.
     *
     * @return ComponentFactory
     */
    public function getFactory();

    /**
     * Return an instance of the component configuration.
     *
     * @return Configuration
     */
    public function getConfiguration();

    /**
     * Return an instance of the component configuration.
     *
     * @return Configuration
     */
    public function getValidator();
}

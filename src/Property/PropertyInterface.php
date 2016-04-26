<?php

namespace PatternBuilder\Property;

interface PropertyInterface
{
    /**
     * Gets the set value.
     *
     * @param string $property_name
     *                              Name of the value to get.
     *
     * @return mixed
     */
    public function get($property_name);

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
     */
    public function prepareRender();
}

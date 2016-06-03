<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Utility;

/**
 * Flatten arbitrary values.
 */
class Flatten
{
    /**
     * Set a value on an array.
     *
     * @param array $data  The array to update.
     * @param mixed $key   The key to append.
     * @param mixed $value The value associated with the key.
     */
    protected static function setArrayValue(array &$data, $key, $value)
    {
        $data[$key] = $value;
    }

    /**
     * Set a value on an object.
     *
     * @param object $data  The object to update.
     * @param mixed  $key   The key to append.
     * @param mixed  $value The value associated with the key.
     */
    protected static function setObjectValue($data, $key, $value)
    {
        $data->{$key} = $value;
    }

    /**
     * Create a flattened object by calling a method on all values provided.
     *
     * @param mixed  $values The traversable values to flatten.
     * @param string $method The method name to call on all values.
     *
     * @return \stdClass|array|null The flatten values.
     */
    public static function byObjectMethod($values, $method)
    {
        if (isset($values) && Inspector::isTraversable($values)) {
            if (is_array($values)) {
                $flat_values = array();
                $setter = 'setArrayValue';
            } else {
                $flat_values = new \stdClass();
                $setter = 'setObjectValue';
            }

            foreach ($values as $key => $value) {
                if (is_object($value) && method_exists($value, $method)) {
                    $flat_value = $value->{$method}();
                    static::$setter($flat_values, $key, $flat_value);
                } else {
                    static::$setter($flat_values, $key, $value);
                }
            }

            return $flat_values;
        }

        return $values;
    }
}

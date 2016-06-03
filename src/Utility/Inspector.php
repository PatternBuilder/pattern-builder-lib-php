<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Utility;

/**
 * Generic inspections of values.
 */
class Inspector
{
    /**
     * Determine if a value is empty.
     *
     * @param string $value The value to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public static function isEmpty($value)
    {
        if (!isset($value)) {
            return true;
        } elseif (is_bool($value)) {
            return false;
        } elseif ($value === 0) {
            return false;
        } elseif (empty($value)) {
            return true;
        } elseif (is_object($value) && method_exists($value, 'isEmpty')) {
            return $value->isEmpty();
        } elseif (static::isTraversable($value)) {
            foreach ($value as $k => $val) {
                if (!static::isEmpty($val)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if the value can be traversed with foreach.
     *
     * @param mixed $value
     *                     Variable to be examined.
     *
     * @return bool
     *              TRUE if $value can be traversed with foreach.
     */
    public static function isTraversable($value)
    {
        return is_array($value) || (is_object($value) &&  (get_class($value) == 'stdClass' || $value instanceof \Traversable));
    }
}

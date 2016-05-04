<?php

namespace PatternBuilder\Property;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class PropertyAbstract
{

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

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
     * Determine if a value is empty.
     *
     * @param string $value The value to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public function isEmptyValue($value) {
        if (!isset($value)) {
            return true;
        }
        elseif (is_bool($value)) {
            return false;
        }
        elseif ($value === 0) {
            return false;
        }
        elseif (empty($value)) {
            return true;
        }
        elseif (is_object($value)) {
          if ($value instanceof PropertyInterface) {
              return $value->isEmpty();
          }
          elseif (get_class($value) == 'stdClass') {
            foreach ($value as $k => $val) {
                if (!$this->isEmptyValue($val)) {
                    return false;
                }
            }

            return true;
          }
        }
        elseif (is_array($value)) {
            foreach ($value as $k => $val) {
                if (!$this->isEmptyValue($val)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if a given property contains data.
     *
     * @param string $property_name The property to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public function isEmpty($property_name = NULL)
    {
        $value = $this->get($property_name);
        return $this->isEmptyValue($value);
    }
}

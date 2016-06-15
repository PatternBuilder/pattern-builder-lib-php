<?php

namespace PatternBuilder\Test;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * This Logger can used during tests to echo messages to the console.
 */
class TestLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        switch ($level) {
          case LogLevel::EMERGENCY:
          case LogLevel::ALERT:
          case LogLevel::CRITICAL:
          case LogLevel::ERROR:
          case LogLevel::WARNING:
            $log_message = $context ? strtr($message, $context) : $message;
            fwrite(STDOUT, "\n".strtoupper($level).': '.$log_message."\n");
            break;
      }
    }
}

<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Event;

use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base console event
 */
abstract class ConsoleEvent extends Event
{
    public const COMMAND = 'm6web.console.command';
    public const TERMINATE = 'm6web.console.terminate';
    public const ERROR = 'm6web.console.error';
    public const EXCEPTION = 'm6web.console.exception';

    final public function __construct(
        protected BaseConsoleEvent $originalEvent,
        protected ?float  $startTime,
        protected ?float $executionTime
    ) {
    }

    /**
     * Map calls to original event
     *
     * @param string $name
     * @param array  $parameters
     */
    public function __call($name, $parameters)
    {
        return call_user_func_array(
            [$this->originalEvent, $name],
            $parameters
        );
    }

    /**
     * Get command start time
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * Get command execution time in ms
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime * 1000;
    }

    /**
     * Alias of getExecutionTime
     * Allows timer simple usage
     */
    public function getTiming(): float
    {
        return $this->getExecutionTime();
    }

    /**
     * Get peak memory usage
     */
    public function getPeakMemory(): int
    {
        $memory = memory_get_peak_usage(true);

        return ($memory > 1024 ? intval($memory / 1024) : 0);
    }

    public function getOriginalEvent(): BaseConsoleEvent
    {
        return $this->originalEvent;
    }

    /**
     * Get an underscored command name, if available
     */
    public function getUnderscoredCommandName(): ?string
    {
        $command = $this->getOriginalEvent()->getCommand();

        if (!is_null($command)) {
            return str_replace(':', '_', $command->getName());
        }

        return null;
    }

    /**
     * Create new event object
     *
     * @throws \InvalidArgumentException
     */
    public static function createFromConsoleEvent(BaseConsoleEvent $e, ?float $startTime = null, ?float $executionTime = null): static
    {
        if (static::support($e)) {
            return new static($e, $startTime, $executionTime);
        } else {
            throw new \InvalidArgumentException('Invalid event type.');
        }
    }

    /**
     * Check if given event is supported by current class
     */
    protected static function support(BaseConsoleEvent $e): bool
    {
        return true;
    }
}

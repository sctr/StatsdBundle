<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Listener;

use M6Web\Bundle\StatsdBundle\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Listen to symfony command events
 * then trigger new custom events
 */
class ConsoleListener
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * Time when command started
     */
    protected ?float $startTime = null;

    public function __construct()
    {
        $this->startTime = null;
        $this->eventDispatcher = null;
    }

    /**
     * Define event dispatch
     */
    public function setEventDispatcher(EventDispatcherInterface $ev): void
    {
        $this->eventDispatcher = $ev;
    }

    public function onCommand(BaseConsoleEvent $e): void
    {
        $this->startTime = microtime(true);

        $this->dispatch($e, ConsoleEvent::COMMAND);
    }

    public function onTerminate(ConsoleTerminateEvent $e): void
    {
        // For non-0 exit command, fire an ERROR event
        if ($e->getExitCode() != 0) {
            $this->dispatch($e, ConsoleEvent::ERROR);
        }

        $this->dispatch($e, ConsoleEvent::TERMINATE);
    }

    public function onException(BaseConsoleEvent $e): void
    {
        $this->dispatch($e, ConsoleEvent::EXCEPTION);
    }

    /**
     * Dispatch custom event
     */
    protected function dispatch(BaseConsoleEvent $e, string $eventName): bool|object
    {
        if (null !== $this->eventDispatcher) {
            /** @var ConsoleEvent $class */
            $class = str_replace(
                'Symfony\Component\Console\Event',
                'M6Web\Bundle\StatsdBundle\Event',
                get_class($e)
            );

            $finaleEvent = $class::createFromConsoleEvent(
                $e,
                $this->startTime,
                null !== $this->startTime ? microtime(true) - $this->startTime : null
            );

            return $this->eventDispatcher->dispatch($finaleEvent);
        } else {
            return false;
        }
    }
}

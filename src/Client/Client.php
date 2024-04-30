<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Client;

use M6Web\Bundle\StatsdBundle\Statsd\MonitorableEventInterface;
use M6Web\Component\Statsd\Client as BaseClient;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class that extends base statsd client, to handle auto-increment from event dispatcher notifications
 */
class Client extends BaseClient
{
    protected array $listenedEvents = [];

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    protected ?int $toSendLimit;

    /**
     * getter for listenedEvents
     */
    public function getListenedEvents(): array
    {
        return $this->listenedEvents;
    }

    /**
     * Add an event to listen
     *
     * @param string $eventName   The event name to listen
     * @param array  $eventConfig The event handler configuration
     */
    public function addEventToListen(string $eventName, array $eventConfig): void
    {
        $this->listenedEvents[$eventName] = $eventConfig;
    }

    /**
     * Set toSend limit
     */
    public function setToSendLimit(int $toSendLimit): self
    {
        $this->toSendLimit = $toSendLimit;

        return $this;
    }

    /**
     * set the property accessor used in replaceConfigPlaceholder
     */
    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): self
    {
        $this->propertyAccessor = $propertyAccessor;

        return $this;
    }

    /**
     * Handle an event
     *
     * @throws Exception
     */
    public function handleEvent(Event $event, ?string $name = null): void
    {
        if (!isset($this->listenedEvents[$name])) {
            return;
        }

        $config = $this->listenedEvents[$name];
        $immediateSend = false;
        $tags = $this->mergeTags($event, $config);

        // replace placeholders in tags
        $tags = array_map(function ($tag) use ($event, $name) {
            return $this->replaceConfigPlaceholder($event, $name, $tag);
        }, $tags);

        foreach ($config as $conf => $confValue) {
            // increment
            if ('increment' === $conf) {
                $this->increment($this->replaceConfigPlaceholder($event, $name, $confValue), 1, $tags);
            } elseif ('decrement' === $conf) {
                $this->decrement($this->replaceConfigPlaceholder($event, $name, $confValue), 1, $tags);
            } elseif ('count' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->count($this->replaceConfigPlaceholder($event, $name, $confValue), $value, 1, $tags);
            } elseif ('gauge' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->gauge($this->replaceConfigPlaceholder($event, $name, $confValue), $value, 1, $tags);
            } elseif ('set' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->set($this->replaceConfigPlaceholder($event, $name, $confValue), $value, 1, $tags);
            } elseif ('timing' === $conf) {
                $this->addTiming($event, 'getTiming', $this->replaceConfigPlaceholder($event, $name, $confValue), $tags);
            } elseif (('custom_timing' === $conf) and is_array($confValue)) {
                $this->addTiming($event, $confValue['method'], $this->replaceConfigPlaceholder($event, $name, $confValue['node']), $tags);
            } elseif ('immediate_send' === $conf) {
                $immediateSend = $confValue;
            } elseif ('tags' === $conf) {
                // nothing
            } else {
                throw new Exception('configuration : '.$conf.' not handled by the StatsdBundle or its value is in a wrong format.');
            }
        }

        if (null !== $this->toSendLimit && count($this->getToSend()) >= $this->toSendLimit) {
            $immediateSend = true;
        }

        if ($immediateSend) {
            $this->send();
        }
    }

    /**
     * getEventValue
     *
     * @throws Exception
     */
    private function getEventValue(Event $event, string $method): mixed
    {
        if (!method_exists($event, $method)) {
            throw new Exception('The event class '.get_class($event).' must have a '.$method.' method in order to mesure value');
        }

        return call_user_func([$event, $method]);
    }

    /**
     * Factorisation of the timing method
     * find the value timed
     *
     * @param Event $event        Event
     * @param string $timingMethod Callable method in the event
     * @param string $node         Node
     * @param array  $tags         Tags key => value for influxDb
     *
     * @throws Exception
     */
    private function addTiming(Event $event, string $timingMethod, string $node, array $tags = []): void
    {
        $timing = $this->getEventValue($event, $timingMethod);
        if ($timing > 0) {
            $this->timing($node, $timing, 1, $tags);
        }
    }

    /**
     * Replaces a string with a method name
     *
     * @param Event  $event     An event
     * @param string $eventName The name of the event
     * @param string $string    The node in which the replacing will happen
     */
    private function replaceConfigPlaceholder(Event $event, string $eventName, string $string): string
    {
        // `event->getName()` is deprecated, we have to replace <name> directly with $eventName
        $string = str_replace('<name>', $eventName, $string);

        if ((preg_match_all('/<([^>]*)>/', $string, $matches) > 0) && ($this->propertyAccessor !== null)) {
            $tokens = $matches[1];
            foreach ($tokens as $token) {
                $value = (string) $this->propertyAccessor->getValue($event, $token);
                $string = str_replace('<'.$token.'>', $value, $string);
            }
        }

        return $string;
    }

    /**
     * Merge config tags with tags manually sent with the event
     */
    private function mergeTags(Event $event, array $config): array
    {
        $configTags = $config['tags'] ?? [];

        if ($event instanceof MonitorableEventInterface) {
            return array_merge($configTags, $event->getTags());
        }

        return $configTags;
    }
}

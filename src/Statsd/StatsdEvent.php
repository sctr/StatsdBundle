<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event for this bundle event dispatching
 */
class StatsdEvent extends GenericEvent implements MonitorableEventInterface
{
    /**
     * getTiming
     */
    public function getTiming()
    {
        return $this->getSubject();
    }

    /**
     * getValue
     */
    public function getValue()
    {
        return $this->getSubject();
    }

    /**
     * array of tags [key => value]
     */
    public function getTags(): array
    {
        return $this->hasArgument('tags') ? $this->getArgument('tags') : [];
    }
}

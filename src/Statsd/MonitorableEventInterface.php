<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Statsd;

/**
 * Interface to implement when creating a custom event to ensure the full capability of the bundle to handle it
 */
interface MonitorableEventInterface
{
    /**
     * the measured value
     */
    public function getValue();

    /**
     * array of tags [key => value]
     */
    public function getTags(): array;
}

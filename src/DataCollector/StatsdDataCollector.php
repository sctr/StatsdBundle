<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\DataCollector;

use M6Web\Bundle\StatsdBundle\Client\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Handle datacollector for statsd
 */
class StatsdDataCollector extends DataCollector
{
    private array $statsdClients = [];

    /**
     * Construct the data collector
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset the data collector to initial state
     */
    public function reset(): void
    {
        $this->statsdClients = [];
        $this->data = [
            'clients' => [],
            'operations' => 0,
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            foreach ($this->statsdClients as $clientName => $client) {
                $clientInfo = [
                    'name' => $clientName,
                    'operations' => [],
                ];
                foreach ($client->getToSend() as $operation) {
                    if ($operation) {
                        $this->data['operations']++;
                        $message = $operation['message'];

                        $clientInfo['operations'][] = [
                            'server' => $operation['server'],
                            'node' => $message->getNode(),
                            'value' => $message->getValue(),
                            'sample' => $message->getSampleRate(),
                            'unit' => $message->getUnit(),
                        ];
                    }
                }
                $this->data['clients'][] = $clientInfo;
            }
        }
    }

    /**
     * Add a statsd client to monitor.
     *
     * @param string $clientAlias  The client alias
     * @param Client $statsdClient A statsd client instance
     */
    public function addStatsdClient(string $clientAlias, Client $statsdClient): void
    {
        $this->statsdClients[$clientAlias] = $statsdClient;
    }

    /**
     * Collect the data
     *
     * @param Request    $request   The request object
     * @param Response   $response  The response object
     * @param \Throwable $exception An exception
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
    }

    /**
     * Return the list of statsd operations
     *
     * @return Client[] operations list
     */
    public function getClients(): array
    {
        return $this->data['clients'];
    }

    /**
     * Return the number of statsd operations
     *
     * @return int the number of operations
     */
    public function getOperations(): int
    {
        return $this->data['operations'];
    }

    /**
     * Return the name of the collector
     *
     * @return string data collector name
     */
    public function getName(): string
    {
        return 'statsd';
    }
}

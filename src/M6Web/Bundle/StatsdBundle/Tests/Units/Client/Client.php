<?php

namespace M6Web\Bundle\StatsdBundle\Tests\Units\Client;

require_once __DIR__.'/../../../../../../../vendor/autoload.php';

use mageekguy\atoum;

/**
* Client class
*/
class Client extends atoum\test
{

    /**
    * return a mocked client
    *
    * @return Clienct
    */
    protected function getMockedClient()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->orphanize('increment');
        $this->mockGenerator->orphanize('timing');
        $client = new \mock\M6\Bundle\StatsdBundle\Client\Client();

        return $client;
    }

    /**
    * testHandleEventWithValidConfig
    */
    public function testHandleEventWithValidConfigIncrement()
    {

        $client = $this->getMockedClient();

        $event = new \Symfony\Component\EventDispatcher\Event();
        $event->setName('test');

        $client->addEventToListen('test', array(
            'increment' => 'stats.<name>'
        ));

        $this->if($client->handleEvent($event))
            ->then
            ->mock($client)
                ->call('increment')
                    ->once()
                    ->withArguments('stats.test');

    }

    /**
    * test handle event with an invalid stats
    */
    public function testHandleEventWithInvalidConfigIncrement()
    {
        $client = $this->getMockedClient();

        $client->addEventToListen('test', array(
            'increment' => 'stats.<toto>'
        ));

        $this->exception( function() use ($client) {
            $event = new \Symfony\Component\EventDispatcher\Event();
            $event->setName('test');

            $client->handleEvent($event);
        });
    }

    /**
    * test handleEvent method with event without getTimingMethod
    */
    public function testHandleEventWithInvalidEventTiming()
    {
        $client = $this->getMockedClient();

        $client->addEventToListen('test', array(
            'timing' => 'stats.<name>'
        ));

        $this->exception( function() use ($client) {
            $event = new \Symfony\Component\EventDispatcher\Event();
            $event->setName('test');

            $client->handleEvent($event);
        });
    }

}

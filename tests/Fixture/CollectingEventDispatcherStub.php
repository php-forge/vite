<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Fixture;

use PHPForge\Vite\Event\AssetsResolved;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Event dispatcher that records every dispatched event and forwards resolutions to the configured listeners.
 *
 * Lets a test observe real dispatch order and count without a mock.
 */
final class CollectingEventDispatcherStub implements EventDispatcherInterface
{
    /**
     * @var list<object> Events dispatched so far, in dispatch order.
     */
    public array $events = [];

    /**
     * @param list<callable(AssetsResolved): void> $listeners Listeners invoked for every resolved-assets event.
     */
    public function __construct(private readonly array $listeners = []) {}

    /**
     * Records the event and forwards it to every listener.
     *
     * @param object $event Event to dispatch.
     *
     * @return object The dispatched event.
     */
    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        if ($event instanceof AssetsResolved) {
            foreach ($this->listeners as $listener) {
                $listener($event);
            }
        }

        return $event;
    }
}

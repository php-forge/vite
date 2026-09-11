<?php

declare(strict_types=1);

namespace PHPForge\Vite\Debug;

use PHPForge\Debug\CollectorInterface;
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Event\AssetsResolved;
use PHPForge\Vite\Vite;
use Psr\EventDispatcher\EventDispatcherInterface;

use function count;

/**
 * Listens to completed Vite resolutions and captures their existing configuration and manifest values.
 *
 * Doubles as a single-listener {@see EventDispatcherInterface}, so a host without a PSR-14 dispatcher passes the
 * collector itself to {@see Vite} instead of authoring one.
 */
final class ViteCollector implements CollectorInterface, EventDispatcherInterface
{
    /**
     * @var list<AssetsResolved> Resolutions observed in the active cycle, in dispatch order.
     */
    private array $resolutions = [];
    /**
     * @var bool Whether the collector is observing the active cycle.
     */
    private bool $started = false;

    /**
     * Records the completed resolution while the collector observes the active cycle.
     *
     * @param AssetsResolved $event Completed resolution dispatched by the Vite integration.
     */
    public function __invoke(AssetsResolved $event): void
    {
        if ($this->started) {
            $this->resolutions[] = $event;
        }
    }

    /**
     * Returns the captured components for every resolution observed in the active cycle.
     *
     * @return array<string, mixed>|null Captured components, or `null` outside an active cycle.
     */
    public function capture(): array|null
    {
        if ($this->started === false) {
            return null;
        }

        $components = [];

        foreach ($this->resolutions as $index => $event) {
            $components[] = $this->component($event, $index + 1);
        }

        return ['components' => $components];
    }

    /**
     * Forwards a completed resolution to this collector.
     *
     * @param object $event Dispatched event; anything other than an {@see AssetsResolved} is returned untouched.
     *
     * @return object The dispatched event.
     */
    public function dispatch(object $event): object
    {
        if ($event instanceof AssetsResolved) {
            $this($event);
        }

        return $event;
    }

    /**
     * Returns the stable ID associating the capture with the Vite panel.
     *
     * @return string Stable collector ID.
     */
    public function id(): string
    {
        return 'vite';
    }

    /**
     * Stops observing and discards the resolutions captured in the completed cycle.
     */
    public function shutdown(): void
    {
        $this->started = false;
        $this->resolutions = [];
    }

    /**
     * Starts observing resolutions for a new cycle.
     */
    public function startup(): void
    {
        $this->started = true;
    }

    /**
     * Collects the production-manifest chunks observed in one resolution.
     *
     * @param AssetsResolved $event Completed resolution observed in the active cycle.
     *
     * @return list<array<string, mixed>> Captured production-manifest chunks in manifest order.
     */
    private function chunks(AssetsResolved $event): array
    {
        $chunks = [];

        foreach ($event->manifest?->chunks() ?? [] as $chunk) {
            $chunks[] = [
                'name' => $chunk->key,
                'file' => $chunk->file,
                'cssCount' => count($chunk->css()),
                'imports' => count($chunk->imports()),
                'isEntry' => $chunk->isEntry(),
            ];
        }

        return $chunks;
    }

    /**
     * Describes one observed resolution as a portable component payload.
     *
     * @param AssetsResolved $event Completed resolution observed in the active cycle.
     * @param int $number Position of the resolution in the active cycle, used to build the component ID.
     *
     * @return array<string, mixed> Captured configuration and manifest values for the component.
     */
    private function component(AssetsResolved $event, int $number): array
    {
        $configuration = $event->configuration;

        if ($configuration instanceof DevelopmentConfiguration) {
            return [
                'id' => $this->id() . '-' . $number,
                'class' => Vite::class,
                'implementation' => 'modern',
                'inspectionAvailable' => true,
                'mode' => 'development',
                'entrypoints' => $event->entrypoints,
                'baseUrl' => '',
                'devServerUrl' => $configuration->devServerUrl,
                'manifestPath' => '',
                'includeViteClient' => $configuration->includeViteClient,
                'modulePreload' => null,
                'chunks' => [],
            ];
        }

        return [
            'id' => $this->id() . '-' . $number,
            'class' => Vite::class,
            'implementation' => 'modern',
            'inspectionAvailable' => true,
            'mode' => 'production',
            'entrypoints' => $event->entrypoints,
            'baseUrl' => $configuration->assetBaseUrl,
            'devServerUrl' => null,
            'manifestPath' => $configuration->manifestPath,
            'includeViteClient' => null,
            'modulePreload' => $configuration->modulePreload,
            'chunks' => $this->chunks($event),
        ];
    }
}

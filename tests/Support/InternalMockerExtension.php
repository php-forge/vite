<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Support;

use PHPUnit\Event\Test\{Finished, FinishedSubscriber, PreparationStarted, PreparationStartedSubscriber};
use PHPUnit\Event\TestSuite\{Started, StartedSubscriber};
use PHPUnit\Runner\Extension\{Extension, Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use Xepozz\InternalMocker\{Mocker, MockerState};

/**
 * Loads and resets namespaced PHP function replacements used by filesystem failure tests.
 */
final class InternalMockerExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new class implements StartedSubscriber {
                public function notify(Started $event): void
                {
                    InternalMockerExtension::load();
                }
            },
            new class implements PreparationStartedSubscriber {
                public function notify(PreparationStarted $event): void
                {
                    MockerState::resetState();
                }
            },
            new class implements FinishedSubscriber {
                public function notify(Finished $event): void
                {
                    MockerState::resetState();
                }
            },
        );
    }

    public static function load(): void
    {
        $mocker = new Mocker(
            __DIR__ . '/../../runtime/.phpunit.cache/internal-mocker/mocks.php',
            __DIR__ . '/internal-mocker-stubs.php',
        );
        $mocker->load(
            [
                [
                    'namespace' => 'PHPForge\\Vite\\Manifest',
                    'name' => 'file_get_contents',
                ],
                [
                    'namespace' => 'PHPForge\\Vite\\Manifest',
                    'name' => 'stat',
                ],
            ],
        );

        MockerState::saveState();
    }
}

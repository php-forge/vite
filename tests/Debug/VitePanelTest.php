<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Debug;

use InvalidArgumentException;
use PHPForge\Debug\PanelView;
use PHPForge\Vite\Debug\VitePanel;
use PHPForge\Vite\Tests\Provider\VitePanelProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function glob;
use function json_decode;
use function json_encode;
use function str_replace;

/**
 * Unit tests for {@see VitePanel} component overviews, build chunks, empty states, and diagnostics validation.
 *
 * {@see VitePanelProvider} for test case data providers.
 *
 * @phpstan-import-type Block from PanelView
 * @phpstan-import-type GroupBlock from PanelView
 * @phpstan-import-type Inline from PanelView
 * @phpstan-import-type OverviewBlock from PanelView
 * @phpstan-import-type Pair from PanelView
 * @phpstan-import-type TableBlock from PanelView
 */
final class VitePanelTest extends TestCase
{
    public function testChunksAndMixedModesRemainProviderOwned(): void
    {
        $panel = new VitePanel();

        $components = VitePanelProvider::components('production');

        $components[0]['chunks'] = [
            [
                'name' => 'app.js',
                'file' => '0',
                'cssCount' => 2,
                'imports' => 3,
                'isEntry' => true,
            ],
        ];

        $view = $panel->present(['components' => $components]);

        $content = self::group(self::blockAt($view, 0))['content'];
        $rows = self::table(self::blockAt($content, 2))['rows'];

        $cell = $rows[0][2] ?? self::fail('Chunks must use the shared table contract.');

        self::assertSame(
            '0',
            self::textValue($cell),
            'A zero string is not an empty filename.',
        );

        $components[] = VitePanelProvider::components('development')[0];

        self::assertSame(
            '2 components · Mixed',
            self::metricValue($panel->present(['components' => $components])->toolbarMetrics(), 0),
            'Different modes must be explicit.',
        );
    }

    public function testCompleteDescriptionsMatchReviewedFixtures(): void
    {
        $paths = glob(__DIR__ . '/fixtures/*.input.json');

        self::assertNotFalse(
            $paths,
            'The capture fixture directory must be readable.',
        );
        self::assertNotEmpty(
            $paths,
            'The complete-description fixtures must be present.',
        );

        foreach ($paths as $path) {
            $input = file_get_contents($path);

            self::assertNotFalse(
                $input,
                'The stored capture fixture must be readable.',
            );

            /** @var array<string, mixed> $data */
            $data = json_decode($input, true, flags: JSON_THROW_ON_ERROR);

            $view = (new VitePanel())->present($data);

            self::assertStringEqualsFile(
                str_replace('.input.json', '.view.json', $path),
                json_encode(
                    $view,
                    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ) . "\n",
                'The complete semantic description must preserve content, order, and styles.',
            );
        }
    }

    public function testMetadataAndEmptyCapture(): void
    {
        $panel = new VitePanel();

        self::assertSame(
            'vite',
            $panel->id(),
            'The persisted panel ID must stay stable.',
        );
        self::assertSame(
            'Vite',
            $panel->name(),
            'The panel title must stay stable.',
        );
        self::assertSame(
            'brand-javascript',
            $panel->icon(),
            'The panel must reuse the existing icon.',
        );

        $view = $panel->present(['components' => []]);

        self::assertFalse(
            $view->isActive(),
            'An empty capture must not activate navigation.',
        );
        self::assertSame(
            [],
            $view->toolbarMetrics(),
            'An empty capture must not add a toolbar metric.',
        );
        self::assertSame(
            '0',
            self::metricValue($view->summaryMetrics(), 0),
            'The summary must retain the empty count.',
        );
    }

    #[DataProviderExternal(VitePanelProvider::class, 'modes')]
    public function testModeLabelAndGroupRemainProviderOwned(string $mode, string $label): void
    {
        $view = (new VitePanel())->present(['components' => VitePanelProvider::components($mode)]);

        self::assertTrue(
            $view->isActive(),
            'Captured components must activate the panel.',
        );
        self::assertSame(
            $label,
            self::metricValue($view->toolbarMetrics(), 0),
            'The toolbar must describe the captured mode.',
        );
        self::assertSame(
            'Vite component frontend',
            self::group(self::blockAt($view, 0))['label'],
            'The group must identify the component.',
        );
    }

    /**
     * @param array<string, mixed> $capture
     */
    #[DataProviderExternal(VitePanelProvider::class, 'malformedCaptures')]
    public function testThrowInvalidArgumentExceptionForMalformedCapture(array $capture, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            $message,
        );

        (new VitePanel())->present($capture);
    }

    public function testUnavailableInspectionAndFlagsRemainVisible(): void
    {
        $components = VitePanelProvider::components('unknown');

        $components[0]['inspectionAvailable'] = false;
        $components[0]['includeViteClient'] = null;

        $view = (new VitePanel())->present(['components' => $components]);

        $content = self::group(self::blockAt($view, 0))['content'];

        self::assertCount(
            4,
            $content->blocks(),
            'Unavailable inspection must add a warning block.',
        );

        $fields = self::overview(self::blockAt($content, 0))['fields'];

        $field = $fields[9] ?? self::fail('Configuration must remain inspectable.');

        self::assertSame(
            'Unknown',
            self::textValue($field['value']),
            'Unavailable flags must not become disabled.',
        );
    }

    /**
     * @return Block
     */
    private static function blockAt(PanelView $view, int $index): array
    {
        return $view->blocks()[$index] ?? self::fail('The declared presentation structure must be complete.');
    }

    /**
     * @param Block $block
     *
     * @return GroupBlock
     */
    private static function group(array $block): array
    {
        return match ($block['kind']) {
            'group' => $block,
            default => self::fail('Each integration must have an accessible group.'),
        };
    }

    /**
     * @param list<Pair> $metrics
     */
    private static function metricValue(array $metrics, int $index): string
    {
        $metric = $metrics[$index] ?? self::fail('The declared presentation structure must be complete.');

        return self::textValue($metric['value']);
    }

    /**
     * @param Block $block
     *
     * @return OverviewBlock
     */
    private static function overview(array $block): array
    {
        return match ($block['kind']) {
            'overview' => $block,
            default => self::fail('Configuration must remain inspectable.'),
        };
    }

    /**
     * @param Block $block
     *
     * @return TableBlock
     */
    private static function table(array $block): array
    {
        return match ($block['kind']) {
            'table' => $block,
            default => self::fail('Chunks must use the shared table contract.'),
        };
    }

    /**
     * @param Inline $inline
     */
    private static function textValue(array $inline): string
    {
        return match ($inline['kind']) {
            'text' => $inline['value'],
            default => self::fail('The value must be plain text.'),
        };
    }
}

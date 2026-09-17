<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Debug;

use InvalidArgumentException;
use PHPForge\Debug\PanelView;
use PHPForge\Debug\Presenter\{
    BadgeInline,
    Block,
    EmptyStateBlock,
    GroupBlock,
    HeadingBlock,
    Inline,
    OverviewBlock,
    ParagraphBlock,
    SummaryMetric,
    TableBlock,
    TextInline,
    ToolbarMetric
};
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

        $content = self::group(self::blockAt($view, 0))->content;
        $rows = self::table(self::blockAt($content, 2))->rows;

        $cell = $rows[0][2] ?? self::fail('Chunks must use the shared table contract.');

        self::assertSame(
            '0',
            self::textValue($cell),
            'A zero string is not an empty filename.',
        );

        $components[] = VitePanelProvider::components('development')[0];

        self::assertSame(
            '2 components · Mixed',
            self::toolbarValue($panel->present(['components' => $components])->toolbarMetrics(), 0),
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
                    self::describe($view),
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

        self::assertSame(
            [],
            $view->toolbarMetrics(),
            'An empty capture must not add a toolbar metric.',
        );
        self::assertSame(
            '0',
            self::summaryValue($view->summaryMetrics(), 0),
            'The summary must retain the empty count.',
        );
    }

    #[DataProviderExternal(VitePanelProvider::class, 'modes')]
    public function testModeLabelAndGroupRemainProviderOwned(string $mode, string $label): void
    {
        $view = (new VitePanel())->present(['components' => VitePanelProvider::components($mode)]);

        self::assertSame(
            $label,
            self::toolbarValue($view->toolbarMetrics(), 0),
            'The toolbar must describe the captured mode.',
        );
        self::assertSame(
            'Vite component frontend',
            self::group(self::blockAt($view, 0))->label,
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

        $content = self::group(self::blockAt($view, 0))->content;

        self::assertCount(
            4,
            $content->blocks(),
            'Unavailable inspection must add a warning block.',
        );

        $fields = self::overview(self::blockAt($content, 0))->fields;

        $field = $fields[9] ?? self::fail('Configuration must remain inspectable.');

        self::assertSame(
            'Unknown',
            self::textValue($field->value),
            'Unavailable flags must not become disabled.',
        );
    }

    /**
     * @param PanelView $view View to read.
     * @param int $index Position of the block in display order.
     *
     * @return Block Block declared at the requested position.
     */
    private static function blockAt(PanelView $view, int $index): Block
    {
        return $view->blocks()[$index] ?? self::fail('The declared presentation structure must be complete.');
    }

    /**
     * Describes the view as the plain structure stored in the reviewed fixtures.
     *
     * @param PanelView $view View to describe.
     *
     * @return array<string, mixed> Complete description in display order.
     */
    private static function describe(PanelView $view): array
    {
        $summary = [];

        foreach ($view->summaryMetrics() as $metric) {
            $summary[] = ['label' => $metric->label, 'value' => self::describeInline($metric->value)];
        }

        $blocks = [];

        foreach ($view->blocks() as $block) {
            $blocks[] = self::describeBlock($block);
        }

        $toolbar = [];

        foreach ($view->toolbarMetrics() as $metric) {
            $toolbar[] = [
                'label' => $metric->label,
                'value' => ['kind' => 'text', 'value' => $metric->value, 'style' => 'plain'],
            ];
        }

        return ['summary' => $summary, 'blocks' => $blocks, 'toolbar' => $toolbar];
    }

    /**
     * @param Block $block Block to describe.
     *
     * @return array<string, mixed> Block description keyed by its declared fields.
     */
    private static function describeBlock(Block $block): array
    {
        return match (true) {
            $block instanceof EmptyStateBlock => [
                'kind' => 'emptyState',
                'title' => $block->title,
                'paragraphs' => self::describeBlocks($block->paragraphs),
            ],
            $block instanceof GroupBlock => [
                'kind' => 'group',
                'label' => $block->label,
                'content' => self::describe($block->content),
            ],
            $block instanceof HeadingBlock => [
                'kind' => 'heading',
                'title' => $block->title,
                'section' => $block->section,
            ],
            $block instanceof OverviewBlock => [
                'kind' => 'overview',
                'fields' => self::describeFields($block),
                'compact' => $block->compact,
            ],
            $block instanceof ParagraphBlock => [
                'kind' => 'paragraph',
                'content' => self::describeInlines($block->content),
                'tone' => $block->tone?->value,
            ],
            $block instanceof TableBlock => [
                'kind' => 'table',
                'headers' => $block->headers,
                'rows' => self::describeRows($block),
                'styles' => self::describeStyles($block),
                'collapsible' => $block->collapsible,
                'filterable' => $block->filterable,
            ],
            default => self::fail('The description must use a block the panel declares.'),
        };
    }

    /**
     * @param list<ParagraphBlock> $blocks Blocks to describe.
     *
     * @return list<array<string, mixed>> Block descriptions in display order.
     */
    private static function describeBlocks(array $blocks): array
    {
        $described = [];

        foreach ($blocks as $block) {
            $described[] = self::describeBlock($block);
        }

        return $described;
    }

    /**
     * @param OverviewBlock $block Overview whose fields are described.
     *
     * @return list<array<string, mixed>> Field descriptions in display order.
     */
    private static function describeFields(OverviewBlock $block): array
    {
        $described = [];

        foreach ($block->fields as $field) {
            $described[] = ['label' => $field->label, 'value' => self::describeInline($field->value)];
        }

        return $described;
    }

    /**
     * @param Inline $inline Inline value to describe.
     *
     * @return array<string, mixed> Inline description keyed by its declared fields.
     */
    private static function describeInline(Inline $inline): array
    {
        return match (true) {
            $inline instanceof BadgeInline => [
                'kind' => 'badge',
                'label' => $inline->label,
                'tone' => $inline->tone->value,
            ],
            $inline instanceof TextInline => [
                'kind' => 'text',
                'value' => $inline->value,
                'style' => $inline->style->value,
            ],
            default => self::fail('The description must use an inline value the panel declares.'),
        };
    }

    /**
     * @param list<Inline> $inlines Inline values to describe.
     *
     * @return list<array<string, mixed>> Inline descriptions in display order.
     */
    private static function describeInlines(array $inlines): array
    {
        $described = [];

        foreach ($inlines as $inline) {
            $described[] = self::describeInline($inline);
        }

        return $described;
    }

    /**
     * @param TableBlock $block Table whose rows are described.
     *
     * @return list<list<array<string, mixed>>> Row descriptions in display order.
     */
    private static function describeRows(TableBlock $block): array
    {
        $described = [];

        foreach ($block->rows as $row) {
            $described[] = self::describeInlines($row);
        }

        return $described;
    }

    /**
     * @param TableBlock $block Table whose column styles are described.
     *
     * @return array<int, string> Style names keyed by column index.
     */
    private static function describeStyles(TableBlock $block): array
    {
        $described = [];

        foreach ($block->styles as $column => $style) {
            $described[$column] = $style->value;
        }

        return $described;
    }

    /**
     * @param Block $block Block to narrow.
     *
     * @return GroupBlock Narrowed group.
     */
    private static function group(Block $block): GroupBlock
    {
        return match (true) {
            $block instanceof GroupBlock => $block,
            default => self::fail('Each integration must have an accessible group.'),
        };
    }

    /**
     * @param Block $block Block to narrow.
     *
     * @return OverviewBlock Narrowed overview.
     */
    private static function overview(Block $block): OverviewBlock
    {
        return match (true) {
            $block instanceof OverviewBlock => $block,
            default => self::fail('Configuration must remain inspectable.'),
        };
    }

    /**
     * @param list<SummaryMetric> $metrics Summary metrics in display order.
     * @param int $index Position of the metric in display order.
     *
     * @return string Plain-text metric value.
     */
    private static function summaryValue(array $metrics, int $index): string
    {
        $metric = $metrics[$index] ?? self::fail('The declared presentation structure must be complete.');

        return self::textValue($metric->value);
    }

    /**
     * @param Block $block Block to narrow.
     *
     * @return TableBlock Narrowed table.
     */
    private static function table(Block $block): TableBlock
    {
        return match (true) {
            $block instanceof TableBlock => $block,
            default => self::fail('Chunks must use the shared table contract.'),
        };
    }

    /**
     * @param Inline $inline Inline value to read.
     *
     * @return string Plain-text content.
     */
    private static function textValue(Inline $inline): string
    {
        return match (true) {
            $inline instanceof TextInline => $inline->value,
            default => self::fail('The value must be plain text.'),
        };
    }

    /**
     * @param list<ToolbarMetric> $metrics Toolbar metrics in display order.
     * @param int $index Position of the metric in display order.
     *
     * @return string Plain-text metric value.
     */
    private static function toolbarValue(array $metrics, int $index): string
    {
        $metric = $metrics[$index] ?? self::fail('The declared presentation structure must be complete.');

        return $metric->value;
    }
}

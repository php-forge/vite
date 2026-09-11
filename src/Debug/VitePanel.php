<?php

declare(strict_types=1);

namespace PHPForge\Vite\Debug;

use InvalidArgumentException;
use PHPForge\Debug\{ColumnStyle, Panel, PanelView, Tone};
use PHPForge\Vite\Exception\Message;

use function array_is_list;
use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

/**
 * Describes Vite diagnostics without importing a debugger engine or HTML library.
 */
final class VitePanel extends Panel
{
    /**
     * Host-interpreted icon identifier for the Vite panel.
     */
    protected const string ICON = 'brand-javascript';

    /**
     * Stable identifier associating the panel with the Vite capture.
     */
    protected const string ID = 'vite';

    /**
     * Panel title used in the debugger navigation.
     */
    protected const string TITLE = 'Vite';

    /**
     * Composes the panel view from the decoded Vite capture.
     *
     * @param array<string, mixed> $data Decoded Vite diagnostics.
     *
     * @throws InvalidArgumentException If the capture carries no component list or a malformed component.
     *
     * @return PanelView Per-component groups, mode summary, and toolbar entry.
     */
    public function present(array $data): PanelView
    {
        $components = $data['components'] ?? null;

        if (!is_array($components) || !array_is_list($components)) {
            throw new InvalidArgumentException(
                Message::DIAGNOSTICS_COMPONENT_LIST_REQUIRED->getMessage(),
            );
        }

        $view = PanelView::create();

        $modes = [];

        foreach ($components as $component) {
            if (!is_array($component)) {
                throw new InvalidArgumentException(
                    Message::DIAGNOSTICS_COMPONENT_INVALID->getMessage(),
                );
            }

            $modes[] = self::string($component, 'mode');

            $view = $view->group('Vite component ' . self::string($component, 'id'), self::component($component));
        }

        $count = count($components);

        $mode = $count === 0 ? 'Unknown' : self::mode($modes[0] ?? 'unknown');

        foreach ($modes as $value) {
            if ($value !== ($modes[0] ?? null)) {
                $mode = 'Mixed';
            }
        }

        $view = $view->summary($count === 1 ? ' component' : ' components', $count)->active($count !== 0);

        if ($count === 0) {
            return $view->emptyState(
                'No Vite integrations captured',
                'This request did not use an initialized Vite application component.',
            );
        }

        return $view
            ->summary('', $mode, emphasized: false)
            ->toolbar('Vite mode', $count === 1 ? $mode : "{$count} components · {$mode}");
    }

    /**
     * Renders the build-chunk table, or the explanation replacing it when no chunk was captured.
     *
     * @param PanelView $view View carrying the component overview already composed.
     * @param array<array-key, mixed> $data Captured component diagnostics.
     * @param string $mode Resolution mode of the captured component.
     *
     * @throws InvalidArgumentException If the chunk diagnostics are missing or malformed.
     *
     * @return PanelView View completed with the chunk table or its replacement.
     */
    private static function chunks(PanelView $view, array $data, string $mode): PanelView
    {
        $chunks = $data['chunks'] ?? null;

        if (!is_array($chunks) || !array_is_list($chunks)) {
            throw new InvalidArgumentException(Message::DIAGNOSTICS_CHUNK_LIST_INVALID->getMessage());
        }

        if ($chunks === []) {
            return $view->paragraph(
                match ($mode) {
                    'development' => 'Development mode resolves entry points through the dev server.',
                    'production' => 'The Vite manifest is missing or empty; run the front-end build to populate it.',
                    default => 'No build chunks were available for inspection.',
                }
            );
        }

        $rows = [];

        foreach ($chunks as $chunk) {
            if (
                !is_array($chunk)
                || !is_int($chunk['cssCount'] ?? null)
                || !is_int($chunk['imports'] ?? null)
                || !is_bool($chunk['isEntry'] ?? null)
            ) {
                throw new InvalidArgumentException(
                    Message::DIAGNOSTICS_CHUNK_INVALID->getMessage(),
                );
            }

            $rows[] = [
                count($rows) + 1,
                PanelView::strong(self::string($chunk, 'name')),
                self::nonEmpty(self::string($chunk, 'file')),
                $chunk['cssCount'],
                $chunk['imports'],
                $chunk['isEntry'] ? PanelView::badge('entry', Tone::SUCCESS) : '—',
            ];
        }

        return $view->table(
            ['#', 'Chunk', 'Output', 'CSS', 'Imports', 'Entry'],
            $rows,
            styles: [
                1 => ColumnStyle::MONOSPACE,
                2 => ColumnStyle::MONOSPACE,
                3 => ColumnStyle::NUMBER,
                4 => ColumnStyle::NUMBER,
                5 => ColumnStyle::PILL,
            ],
        );
    }

    /**
     * Composes the overview, inspection callout, and chunk table for one captured component.
     *
     * @param array<array-key, mixed> $data Captured component diagnostics.
     *
     * @throws InvalidArgumentException If the component diagnostics are missing or malformed.
     *
     * @return PanelView Component overview followed by its build chunks.
     */
    private static function component(array $data): PanelView
    {
        $mode = self::string($data, 'mode');

        $available = $data['inspectionAvailable'] ?? null;

        if (!is_bool($available)) {
            throw new InvalidArgumentException(
                Message::DIAGNOSTICS_INSPECTION_INVALID->getMessage(),
            );
        }

        $entrypoints = $data['entrypoints'] ?? null;

        if (!is_array($entrypoints) || !array_is_list($entrypoints)) {
            throw new InvalidArgumentException(
                Message::DIAGNOSTICS_ENTRYPOINT_LIST_INVALID->getMessage(),
            );
        }

        foreach ($entrypoints as $entrypoint) {
            if (!is_string($entrypoint)) {
                throw new InvalidArgumentException(
                    Message::DIAGNOSTICS_ENTRYPOINT_TYPE_INVALID->getMessage(),
                );
            }
        }

        $view = PanelView::create()->overview(
            [
                'Component ID' => self::string($data, 'id'),
                'Class' => self::string($data, 'class'),
                'Implementation' => self::string($data, 'implementation'),
                'Mode' => self::mode($mode),
                'Inspection' => PanelView::badge($available ? 'Available' : 'Unavailable', $available
                    ? Tone::SUCCESS
                    : Tone::WARNING),
                'Entry points' => $entrypoints === []
                    ? '—'
                    : implode(', ', $entrypoints),
                'Base URL' => self::nonEmpty(self::string($data, 'baseUrl')),
                'Dev server' => self::nullableString($data, 'devServerUrl') ?? '—',
                'Manifest' => self::nonEmpty(self::string($data, 'manifestPath')),
                'Vite client' => $mode === 'production'
                    ? 'Not applicable'
                    : self::flag($data['includeViteClient'] ?? null),
                'Module preload' => $mode === 'development'
                    ? 'Not applicable'
                    : self::flag($data['modulePreload'] ?? null),
            ],
            compact: true,
        );

        if (!$available) {
            $view = $view->callout(
                Tone::WARNING,
                'Runtime inspection is unavailable for this component. Its public configuration could not be read without changing application state.',
            );
        }

        return self::chunks($view->heading('Build chunks', section: true), $data, $mode);
    }

    /**
     * Describes a tri-state configuration flag for the overview.
     *
     * @param mixed $value Captured flag value.
     *
     * @throws InvalidArgumentException If the value is neither a boolean nor `null`.
     *
     * @return string Human-readable flag state.
     */
    private static function flag(mixed $value): string
    {
        return match ($value) {
            true => 'Enabled', false => 'Disabled', null => 'Unknown',
            default => throw new InvalidArgumentException(
                Message::DIAGNOSTICS_FLAG_INVALID->getMessage(),
            ),
        };
    }

    /**
     * Returns the human-readable label for a captured resolution mode.
     *
     * @param string $mode Captured resolution mode.
     *
     * @throws InvalidArgumentException If the mode is not a known Vite diagnostics mode.
     *
     * @return string Human-readable mode label.
     */
    private static function mode(string $mode): string
    {
        return match ($mode) {
            'development' => 'Development', 'production' => 'Production', 'unknown' => 'Unknown',
            default => throw new InvalidArgumentException(
                Message::DIAGNOSTICS_MODE_UNKNOWN->getMessage(),
            ),
        };
    }

    /**
     * Returns the value, or a dash placeholder when the capture left it empty.
     *
     * @param string $value Captured value.
     *
     * @return string Captured value, or a dash placeholder when empty.
     */
    private static function nonEmpty(string $value): string
    {
        return $value === '' ? '—' : $value;
    }

    /**
     * Returns the captured value for the key when it is a string or `null`.
     *
     * @param array<array-key, mixed> $data Captured component diagnostics.
     * @param string $key Diagnostics key to read.
     *
     * @throws InvalidArgumentException If the value is neither a string nor `null`.
     *
     * @return string|null Captured string, or `null` when the key holds no value.
     */
    private static function nullableString(array $data, string $key): string|null
    {
        $value = $data[$key] ?? null;

        if ($value !== null && !is_string($value)) {
            throw new InvalidArgumentException(
                Message::DIAGNOSTICS_VALUE_NOT_NULLABLE_STRING->getMessage($key),
            );
        }

        return $value;
    }

    /**
     * Returns the captured value for the key, rejecting any non-string type.
     *
     * @param array<array-key, mixed> $data Captured component diagnostics.
     * @param string $key Diagnostics key to read.
     *
     * @throws InvalidArgumentException If the value is not a string.
     *
     * @return string Captured string.
     */
    private static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value)) {
            throw new InvalidArgumentException(
                Message::DIAGNOSTICS_VALUE_NOT_STRING->getMessage($key),
            );
        }

        return $value;
    }
}

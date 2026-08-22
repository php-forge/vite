<?php

declare(strict_types=1);

namespace PHPForge\Vite\Configuration;

use PHPForge\Vite\Development\InlineModuleProviderInterface;
use PHPForge\Vite\Exception\ConfigurationException;
use PHPForge\Vite\Support\{EntrypointNormalizer, Url};

/**
 * Immutable configuration for Vite development-server resolution.
 */
final readonly class DevelopmentConfiguration
{
    public string $devServerUrl;
    /**
     * @var list<string>
     */
    public array $entrypoints;
    /**
     * @var list<InlineModuleProviderInterface>
     */
    public array $inlineModuleProviders;

    /**
     * @param list<string> $entrypoints
     * @param list<InlineModuleProviderInterface> $inlineModuleProviders
     */
    public function __construct(
        string $devServerUrl,
        array $entrypoints = [],
        public bool $includeViteClient = true,
        array $inlineModuleProviders = [],
    ) {
        $this->devServerUrl = Url::normalizeDevServerUrl($devServerUrl);
        $this->entrypoints = EntrypointNormalizer::normalize($entrypoints, false);
        $this->inlineModuleProviders = $this->normalizeProviders($inlineModuleProviders);
    }

    /**
     * @param iterable<mixed> $providers
     *
     * @return list<InlineModuleProviderInterface>
     */
    private function normalizeProviders(iterable $providers): array
    {
        $normalized = [];

        foreach ($providers as $provider) {
            if (!$provider instanceof InlineModuleProviderInterface) {
                throw new ConfigurationException(
                    'Each development inline module provider must implement InlineModuleProviderInterface.',
                );
            }

            $normalized[] = $provider;
        }

        return $normalized;
    }
}

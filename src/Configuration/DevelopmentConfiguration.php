<?php

declare(strict_types=1);

namespace PHPForge\Vite\Configuration;

use PHPForge\Vite\Development\InlineModuleProviderInterface;
use PHPForge\Vite\Exception\{ConfigurationException, Message};
use PHPForge\Vite\Support\Url;

/**
 * Immutable configuration for Vite development-server resolution.
 */
final readonly class DevelopmentConfiguration
{
    public string $devServerUrl;
    /**
     * @var list<InlineModuleProviderInterface>
     */
    public array $inlineModuleProviders;

    /**
     * @param list<InlineModuleProviderInterface> $inlineModuleProviders
     */
    public function __construct(
        string $devServerUrl,
        public bool $includeViteClient = true,
        array $inlineModuleProviders = [],
    ) {
        $this->devServerUrl = Url::normalizeDevServerUrl($devServerUrl);
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
                    Message::DEVELOPMENT_INLINE_MODULE_PROVIDER_INVALID->getMessage(),
                );
            }

            $normalized[] = $provider;
        }

        return $normalized;
    }
}

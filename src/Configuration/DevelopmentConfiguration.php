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
    /**
     * Normalized development-server URL, without a trailing separator.
     */
    public string $devServerUrl;

    /**
     * @var list<InlineModuleProviderInterface> Providers emitted, in order, before the Vite client.
     */
    public array $inlineModuleProviders;

    /**
     * @param string $devServerUrl Absolute HTTP(S) URL of the running Vite development server.
     * @param bool $includeViteClient Whether the `@vite/client` module script is emitted.
     * @param list<mixed> $inlineModuleProviders Providers of application-owned inline modules to validate and emit
     * before the Vite client.
     *
     * @throws ConfigurationException if the development-server URL is not an absolute HTTP(S) URL, or if a provider
     * does not implement {@see InlineModuleProviderInterface}.
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
     * Creates a development-server configuration.
     *
     * @param string $devServerUrl Absolute HTTP(S) URL of the running Vite development server.
     * @param bool $includeViteClient Whether the `@vite/client` module script is emitted.
     * @param list<mixed> $inlineModuleProviders Providers of application-owned inline modules.
     *
     * @throws ConfigurationException if the development-server URL is invalid, or if a provider is unsupported.
     *
     * @return self A new development-server configuration.
     */
    public static function create(
        string $devServerUrl,
        bool $includeViteClient = true,
        array $inlineModuleProviders = [],
    ): self {
        return new self($devServerUrl, $includeViteClient, $inlineModuleProviders);
    }

    /**
     * Rejects any provider that does not satisfy the contract, and reindexes the survivors as a list.
     *
     * @param iterable<mixed> $providers Raw providers supplied by the application.
     *
     * @throws ConfigurationException if a value does not implement {@see InlineModuleProviderInterface}.
     *
     * @return list<InlineModuleProviderInterface> Providers in the order supplied.
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

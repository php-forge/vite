<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use function sprintf;

/**
 * Defines the exception message templates authored by the Vite integration.
 *
 * Use {@see Message::getMessage()} to format a template with `sprintf()` arguments.
 */
enum Message: string
{
    /**
     * The asset base URL is malformed or unsafe.
     *
     * Format: "The \"assetBaseUrl\" value is invalid."
     */
    case ASSET_BASE_URL_INVALID = 'The "assetBaseUrl" value is invalid.';

    /**
     * The asset base URL contains a query or fragment.
     *
     * Format: "The \"assetBaseUrl\" value must not contain a query or fragment."
     */
    case ASSET_BASE_URL_QUERY_OR_FRAGMENT = 'The "assetBaseUrl" value must not contain a query or fragment.';

    /**
     * The absolute asset base URL does not use HTTP(S).
     *
     * Format: "The \"assetBaseUrl\" value must use HTTP(S) when it is an absolute URL."
     */
    case ASSET_BASE_URL_SCHEME_INVALID = 'The "assetBaseUrl" value must use HTTP(S) when it is an absolute URL.';

    /**
     * An asset collection value does not implement the asset contract.
     *
     * Format: "AssetCollection accepts only AssetInterface instances."
     */
    case ASSET_COLLECTION_ITEM_INVALID = 'AssetCollection accepts only AssetInterface instances.';

    /**
     * An asset implementation is not supported by the operation.
     *
     * Format: "Unsupported AssetInterface implementation."
     */
    case ASSET_IMPLEMENTATION_UNSUPPORTED = 'Unsupported AssetInterface implementation.';

    /**
     * A Vite build path contains a dot path segment.
     *
     * Format: "The %s must not contain dot path segments."
     */
    case ASSET_PATH_DOT_SEGMENT = 'The %s must not contain dot path segments.';

    /**
     * A Vite build path is not a valid relative path.
     *
     * Format: "The %s must be a relative Vite build path."
     */
    case ASSET_PATH_INVALID = 'The %s must be a relative Vite build path.';

    /**
     * An asset URL cannot be parsed.
     *
     * Format: "Asset URLs must use a valid URL form."
     */
    case ASSET_URL_FORM_INVALID = 'Asset URLs must use a valid URL form.';

    /**
     * An asset URL is empty or uses an unsafe form.
     *
     * Format: "Asset URLs must be non-empty and use a safe URL form."
     */
    case ASSET_URL_FORM_UNSAFE = 'Asset URLs must be non-empty and use a safe URL form.';

    /**
     * An absolute asset URL does not use HTTP(S).
     *
     * Format: "Absolute asset URLs must use HTTP(S)."
     */
    case ASSET_URL_SCHEME_INVALID = 'Absolute asset URLs must use HTTP(S).';

    /**
     * A CSP nonce is not a non-empty base64 or base64url value.
     *
     * Format: "The CSP nonce must be a non-empty base64 or base64url value."
     */
    case CSP_NONCE_INVALID = 'The CSP nonce must be a non-empty base64 or base64url value.';

    /**
     * A development inline module provider does not implement the provider contract.
     *
     * Format: "Each development inline module provider must implement InlineModuleProviderInterface."
     */
    case DEVELOPMENT_INLINE_MODULE_PROVIDER_INVALID = 'Each development inline module provider must implement '
        . 'InlineModuleProviderInterface.';

    /**
     * The development server URL is not an absolute HTTP(S) URL.
     *
     * Format: "The \"devServerUrl\" value must be an absolute HTTP(S) URL."
     */
    case DEVELOPMENT_SERVER_URL_INVALID = 'The "devServerUrl" value must be an absolute HTTP(S) URL.';

    /**
     * A Vite entrypoint is not a valid relative source path.
     *
     * Format: "Each Vite entrypoint must be a non-empty relative source path."
     */
    case ENTRYPOINT_INVALID = 'Each Vite entrypoint must be a non-empty relative source path.';

    /**
     * A requested entrypoint is absent from the manifest.
     *
     * Format: "The Vite manifest file \"%s\" does not contain the entrypoint \"%s\"."
     */
    case ENTRYPOINT_NOT_FOUND = 'The Vite manifest file "%s" does not contain the entrypoint "%s".';

    /**
     * At least one Vite entrypoint is required.
     *
     * Format: "At least one Vite entrypoint must be configured."
     */
    case ENTRYPOINT_REQUIRED = 'At least one Vite entrypoint must be configured.';

    /**
     * A Vite entrypoint is not a `string`.
     *
     * Format: "Each Vite entrypoint must be a string."
     */
    case ENTRYPOINT_TYPE_INVALID = 'Each Vite entrypoint must be a string.';

    /**
     * A configured filesystem path is not absolute.
     *
     * Format: "The \"%s\" value must be an absolute filesystem path."
     */
    case FILESYSTEM_PATH_INVALID = 'The "%s" value must be an absolute filesystem path.';

    /**
     * An HTML attribute name has invalid syntax.
     *
     * Format: "HTML attribute names must use a safe HTML name syntax."
     */
    case HTML_ATTRIBUTE_NAME_INVALID = 'HTML attribute names must use a safe HTML name syntax.';

    /**
     * An HTML attribute provider returns an unsupported value.
     *
     * Format: "The HTML attribute provider must return an array."
     */
    case HTML_ATTRIBUTE_PROVIDER_RESULT_INVALID = 'The HTML attribute provider must return an array.';

    /**
     * An HTML attribute is reserved, duplicated, or unsafe.
     *
     * Format: "The HTML attribute \"%s\" is reserved or unsafe."
     */
    case HTML_ATTRIBUTE_RESERVED = 'The HTML attribute "%s" is reserved or unsafe.';

    /**
     * An HTML attribute value has an unsupported type or value.
     *
     * Format: "The HTML attribute \"%s\" has an unsupported value."
     */
    case HTML_ATTRIBUTE_VALUE_INVALID = 'The HTML attribute "%s" has an unsupported value.';

    /**
     * An inline module has no source.
     *
     * Format: "Inline module source must not be empty."
     */
    case INLINE_MODULE_SOURCE_EMPTY = 'Inline module source must not be empty.';

    /**
     * A manifest cannot be decoded as JSON.
     *
     * Format: "Unable to decode the Vite manifest file \"%s\": %s"
     */
    case MANIFEST_DECODE_FAILED = 'Unable to decode the Vite manifest file "%s": %s';

    /**
     * A manifest entry has no valid emitted file.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" has no valid \"file\"."
     */
    case MANIFEST_ENTRY_FILE_INVALID = 'The Vite manifest entry "%s" in "%s" has no valid "file".';

    /**
     * A manifest entry has an invalid emitted file path.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" has an invalid \"file\" path."
     */
    case MANIFEST_ENTRY_FILE_PATH_INVALID = 'The Vite manifest entry "%s" in "%s" has an invalid "file" path.';

    /**
     * A manifest contains an invalid entry.
     *
     * Format: "The Vite manifest file \"%s\" contains an invalid entry."
     */
    case MANIFEST_ENTRY_INVALID = 'The Vite manifest file "%s" contains an invalid entry.';

    /**
     * A requested manifest entry is not marked as an entrypoint.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" is not marked as an entrypoint."
     */
    case MANIFEST_ENTRY_NOT_ENTRYPOINT = 'The Vite manifest entry "%s" in "%s" is not marked as an entrypoint.';

    /**
     * A manifest entry has an invalid list field.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" has an invalid \"%s\" list."
     */
    case MANIFEST_FIELD_LIST_INVALID = 'The Vite manifest entry "%s" in "%s" has an invalid "%s" list.';

    /**
     * A manifest entry has an invalid scalar field.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" has an invalid \"%s\" value."
     */
    case MANIFEST_FIELD_VALUE_INVALID = 'The Vite manifest entry "%s" in "%s" has an invalid "%s" value.';

    /**
     * A manifest file cannot be inspected.
     *
     * Format: "Unable to inspect the Vite manifest file \"%s\"."
     */
    case MANIFEST_INSPECTION_FAILED = 'Unable to inspect the Vite manifest file "%s".';

    /**
     * A manifest list contains an invalid item.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" contains an invalid \"%s[%d]\" value."
     */
    case MANIFEST_LIST_ITEM_INVALID = 'The Vite manifest entry "%s" in "%s" contains an invalid "%s[%d]" value.';

    /**
     * A manifest list contains an invalid asset path.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" contains an invalid \"%s[%d]\" path."
     */
    case MANIFEST_LIST_ITEM_PATH_INVALID = 'The Vite manifest entry "%s" in "%s" contains an invalid "%s[%d]" path.';

    /**
     * A manifest file does not exist.
     *
     * Format: "The Vite manifest file \"%s\" does not exist."
     */
    case MANIFEST_NOT_FOUND = 'The Vite manifest file "%s" does not exist.';

    /**
     * A manifest file cannot be read.
     *
     * Format: "Unable to read the Vite manifest file \"%s\"."
     */
    case MANIFEST_READ_FAILED = 'Unable to read the Vite manifest file "%s".';

    /**
     * A manifest entry references a missing import.
     *
     * Format: "The Vite manifest entry \"%s\" in \"%s\" references missing \"%s\" chunk \"%s\"."
     */
    case MANIFEST_REFERENCE_MISSING = 'The Vite manifest entry "%s" in "%s" references missing "%s" chunk "%s".';

    /**
     * A manifest root is not a JSON object.
     *
     * Format: "The Vite manifest file \"%s\" must contain a JSON object."
     */
    case MANIFEST_ROOT_INVALID = 'The Vite manifest file "%s" must contain a JSON object.';

    /**
     * Formats the message template with the supplied arguments.
     *
     * @param int|string ...$argument Values inserted into the template.
     *
     * @return string Formatted exception message.
     */
    public function getMessage(int|string ...$argument): string
    {
        return sprintf($this->value, ...$argument);
    }
}

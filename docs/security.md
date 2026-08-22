# Security and CSP

## HTML escaping

`HtmlRenderer` delegates tag construction and HTML5 attribute escaping to `ui-awesome/html`. Invalid UTF-8 bytes are
substituted rather than passed through. Required attributes such as `src`, `href`, `rel`, `type`, and `nonce` cannot be
overridden.

Inline module source is application-owned executable JavaScript, not untrusted text. The renderer prevents a literal
case-insensitive `</script` sequence from terminating the script element, but it does not sanitize JavaScript. Never build an
`InlineModule` by interpolating untrusted input without a safe JavaScript serialization strategy.

## Custom attribute policy

`HtmlRenderOptions` accepts per-asset-type attributes and an optional per-asset callback:

```php
use PHPForge\Vite\Asset\AssetInterface;
use PHPForge\Vite\Asset\ModuleScript;
use PHPForge\Vite\Html\HtmlRenderOptions;

$options = new HtmlRenderOptions(
    moduleScriptAttributes: ['crossorigin' => true],
    stylesheetAttributes: ['media' => 'screen'],
    attributeProvider: static fn(AssetInterface $asset): array => $asset instanceof ModuleScript
        ? ['data-entry' => 'application']
        : [],
);
```

Attribute names must begin with a letter or underscore and may otherwise contain letters, digits, underscores, or hyphens.
Values may be strings, integers, floats, booleans, or `null`. Boolean `true` emits a valueless attribute; `false` and `null`
omit it. Inline event-handler attributes, `style`, required renderer attributes, nonce overrides, and duplicate names are
rejected.

The callback receives a neutral `AssetInterface` instance and must return an attribute array. A callback value replaces a
per-type value with the same exact key. Names that differ only by case are treated as duplicates and rejected by the
renderer.

## CSP nonce

Generate a cryptographically random nonce for each HTTP response, include it in the application's Content-Security-Policy
header, and pass the same base64 or base64url value to the renderer:

```php
use PHPForge\Vite\Html\HtmlRenderer;
use PHPForge\Vite\Html\HtmlRenderOptions;

$nonce = base64_encode(random_bytes(18));

header("Content-Security-Policy: script-src 'nonce-{$nonce}' 'strict-dynamic'; object-src 'none'; base-uri 'none'");

$html = (new HtmlRenderer())->render(
    $vite->resolve(),
    new HtmlRenderOptions(nonce: $nonce),
);
```

The renderer places the nonce on each generated script and link tag. The application remains responsible for the complete
CSP header, response-specific nonce lifecycle, browser compatibility, and policy directives for other resources.

Development servers commonly require additional `connect-src` origins for HTTP and WebSocket HMR connections and may
serve resources from a separate origin. Configure those directives only in the application's development policy; this
package does not weaken CSP automatically.

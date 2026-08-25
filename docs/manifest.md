# Manifest resolution

Production mode consumes Vite's client build manifest documented by the
[backend integration guide](https://vite.dev/guide/backend-integration.html). The manifest is a JSON object whose keys are
source entrypoints or generated chunk identifiers.

## Supported chunk fields

The loader recognizes the current Vite chunk fields:

| Field            | Type           | Required | Use                                               |
| ---------------- | -------------- | -------- | ------------------------------------------------- |
| `file`           | `string`       | yes      | Generated JavaScript, CSS, or other chunk path.   |
| `src`            | `string`       | no       | Original source path.                             |
| `css`            | `list<string>` | no       | CSS emitted for the chunk.                        |
| `assets`         | `list<string>` | no       | Other generated assets associated with the chunk. |
| `isEntry`        | `bool`         | no       | Marks a client entrypoint.                        |
| `name`           | `string`       | no       | Chunk name reported by Vite.                      |
| `isDynamicEntry` | `bool`         | no       | Marks a dynamic entrypoint.                       |
| `imports`        | `list<string>` | no       | Static manifest references.                       |
| `dynamicImports` | `list<string>` | no       | Lazy manifest references.                         |

Unknown chunk fields are accepted as forward-compatible input and ignored. Known fields must have the documented types.
Every `file`, `css`, and `assets` value must be a safe relative build path. Every static or dynamic reference must identify
another manifest entry.

Consumers constructing chunks directly can use `ManifestChunk::create($key, $file)` or its public two-argument constructor,
then replace optional fields with `withSrc()`, `withCss()`, `withAssets()`, `withEntry()`, `withName()`,
`withDynamicEntry()`, `withImports()`, and `withDynamicImports()`. Each modifier returns a new chunk. Optional values are
read through the corresponding typed getters.

## Initial-page resolution

For each requested entrypoint, the resolver:

1. requires a manifest entry marked with `isEntry: true`;
2. walks static `imports` recursively in dependency-first order;
3. collects entry and imported CSS in stable manifest order;
4. emits a module script for each non-CSS entrypoint;
5. optionally emits modulepreload assets for imported JavaScript chunks.

Asset identities are deduplicated without sorting, so the first discovered location determines output order. A visited set
prevents infinite recursion for malformed circular import graphs and prevents repeated work across multiple entrypoints.
Selected entrypoint scripts are not also emitted as modulepreload assets.

`dynamicImports` are validated but are not placed in the initial page because the browser loads them when the application
executes the corresponding dynamic import. The `assets` field is available through `ManifestChunk::assets()` for consumers
inspecting a manifest, but generic HTML tags cannot be inferred safely from those files and are not emitted automatically.

## Failure behavior

The package throws explicit exceptions for:

- a non-absolute manifest path;
- a missing or unreadable manifest;
- malformed JSON or a non-object root;
- invalid known field types or output paths;
- references to missing chunks;
- a missing requested entrypoint;
- a requested manifest chunk that is not marked as an entrypoint.

There are no silent development fallbacks in production mode.

## Scope boundaries

This package consumes Vite's client build manifest only. It does not consume the SSR manifest, transform HTML, implement
experimental import maps, or decide how arbitrary copied assets should be presented.

## Next steps

- 📚 [Installation guide](installation.md)
- ⚙️ [Configuration reference](configuration.md)
- 💡 [Usage examples](examples.md)
- 🔒 [Security and CSP](security.md)
- 🧪 [Testing guide](testing.md)

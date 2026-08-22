# Installation guide

## Requirements

- [PHP](https://www.php.net/downloads) 8.3 or later.
- The `mbstring` PHP extension.
- [Composer](https://getcomposer.org/download/) 2.x.
- A consumer-managed [Vite](https://vite.dev/) installation when assets are built or served locally.

The package has no runtime dependency on Yii, Inertia, React, Vue, Foxy, Node.js, or a JavaScript package manager.
Its optional HTML rendering API uses the framework-agnostic [`ui-awesome/html`](https://github.com/ui-awesome/html)
library, which includes `ui-awesome/html-helper` transitively.

## Install the PHP package

```bash
composer require php-forge/vite:^0.1
```

## Configure the consuming project

Install Vite and any desired plugins in the application with its chosen JavaScript package manager. The PHP package never
modifies `package.json`, installs Vite, or runs a package manager.

Production resolution requires Vite's client build manifest. Enable it in the application's Vite configuration:

```js
import {defineConfig} from 'vite';

export default defineConfig({
    build: {
        manifest: true,
    },
});
```

With the default output directory, Vite writes the manifest to `dist/.vite/manifest.json`. If `build.manifest` is a
string, Vite uses that value as the manifest path relative to `build.outDir`. Pass the resulting absolute filesystem path
to `ProductionConfiguration`.

Vite injects its modulepreload polyfill into each HTML entry by default. A backend integration that uses a non-HTML custom
entry must import `vite/modulepreload-polyfill` when the polyfill is needed. This package only renders modulepreload assets;
it does not provide that JavaScript polyfill.

## Next steps

- [Configuration reference](configuration.md)
- [Manifest resolution](manifest.md)
- [Usage examples](examples.md)
- [Security and CSP](security.md)

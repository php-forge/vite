# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.5.2 September 25, 2026

- docs: describe the explicit Yii2 `dispatchers` and Yii3 `params`/`events-web` debugger registration in `docs/debugging.md`.
- build(deps): require `php-forge/debug` `^0.4`.

## 0.5.1 September 18, 2026

- fix: correct dependency version for `php-forge/debug` to `^0.3`.

## 0.5.0 September 17, 2026

- build!: require `php-forge/debug` `^0.3`.

## 0.4.0 September 16, 2026

- refactor(debug)!: consume the php-forge/debug `0.2` presenter value objects.

## 0.3.0 September 11, 2026

- feat!: provide a declarative Vite debugger panel through `php-forge/debug`.

## 0.2.1 August 25, 2026

- feat: add `create()` factories for renderers and Vite configurations, and use them in examples and tests.

## 0.2.0 August 25, 2026

- docs: add `Next steps` section with links to installation, usage, configuration, and testing guides.
- feat!: add `Vite::create()` and replace high-arity render-option and manifest-chunk construction with fluent immutable APIs.

## 0.1.0 August 24, 2026

- feat: added a framework-agnostic Vite facade with explicit development and production configuration.
- docs: add class-level PHPDoc for the framework-neutral Vite APIs.
- refactor: remove unreachable renderer and manifest resolver branches, retain filesystem race protection, and reach `100%` code coverage without reflection-based tests.

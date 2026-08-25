# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.2.1 Under development

## 0.2.0 August 25, 2026

- docs: add `Next steps` section with links to installation, usage, configuration, and testing guides.
- feat!: add `Vite::create()` and replace high-arity render-option and manifest-chunk construction with fluent immutable APIs.

## 0.1.0 August 24, 2026

- feat: added a framework-agnostic Vite facade with explicit development and production configuration.
- docs: add class-level PHPDoc for the framework-neutral Vite APIs.
- refactor: remove unreachable renderer and manifest resolver branches, retain filesystem race protection, and reach `100%` code coverage without reflection-based tests.

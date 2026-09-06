# Maho Ignition

[![Latest Version on Packagist](https://img.shields.io/packagist/v/empiricompany/maho-ignition.svg?style=flat-square)](https://packagist.org/packages/empiricompany/maho-ignition)
[![Total Downloads](https://img.shields.io/packagist/dt/empiricompany/maho-ignition.svg?style=flat-square)](https://packagist.org/packages/empiricompany/maho-ignition)

Integrate [Spatie Ignition](https://github.com/spatie/ignition) error pages, optional OpenAI solutions, and optional Flare reporting into Maho.

## Requirements

- PHP 8.3 or newer
- Maho with the `mage_run_installed_exception` and `mage_run_exception` events
- `spatie/ignition` 1.15.1 or newer within the 1.x series

## Installation

Install the module with Composer:

```bash
composer require empiricompany/maho-ignition
```

The package is a Maho module published as `empiricompany/maho-ignition`.

## Configuration

Open **System > Configuration > Advanced > Developer > Ignition Settings**.

Available settings include:

1. **Enabled** — Enable or disable Ignition error pages.
2. **Default Editor** — Select the editor used by Ignition (default: `clipboard`).
3. **Default Theme** — Select `auto`, `light`, or `dark` (default: `auto`).
4. **Save Custom Settings in Session** — Allow session-based editor and theme overrides.
5. **Enable AI-Generated Solutions** — Enable OpenAI-powered solutions.
6. **OpenAI API Key** — Provide the key used by the optional OpenAI integration.
7. **Enable Flare** — Send integration errors to Flare.
8. **Flare API Key** — Provide the key for the Flare project.
9. **Anonymize IP** — Anonymize IP addresses sent to Flare.

The Ignition UI is available only when Maho developer mode is enabled. Flare
reporting is configured separately and can also send reports while developer
mode is enabled; enabling Flare does not make the Ignition UI available in
non-developer mode.

The OpenAI integration is optional. Enable AI-generated solutions only after
installing the additional client:

```bash
composer require openai-php/client
```

`openai-php/client` is required for the AI solutions provider, but is not
required for the Ignition UI or Flare integration.

## Runtime error and Flare reporting limits

Some runtime PHP errors can render Ignition but are **not sent to Flare**:
Ignition uses Maho's handler/renderer, while Flare applies Spatie's lifecycle
and environment guards. Maho's `mage_run_installed_exception` and
`mage_run_exception` hooks cover only exceptions caught by `Mage::run()`.

Pre-bootstrap errors, parse errors, and pre-bootstrap fatals are not
interceptable. The developer-mode path is only an attempt, not a guarantee;
no Maho patch is required. Check the Flare dashboard and PHP/application logs
separately; never log the Flare key.

### Ignition configuration endpoint

The `POST /_ignition/update-config` endpoint is available through the
`developer/ignition` path and accepts a JSON request body. The implementation
accepts only these configuration keys:

- `theme` — `auto`, `light`, or `dark`.
- `editor` — one of the editor options exposed by Ignition.

## Screenshots

| Ignition error page | Configuration |
|:---:|:---:|
| ![Ignition error page](https://github.com/empiricompany/openmage_ignition/assets/5071467/f7c18948-de37-4071-b8e7-e185112c89aa) | ![Ignition settings](https://github.com/empiricompany/openmage_ignition/assets/5071467/d101ac76-92c2-40b3-8dcd-67efa9d1779c) |

The screenshots are visual references inherited from the OpenMage
integration; they do not document Maho-specific runtime behavior.

## License

This module is released under the Open Software License 3.0 (OSL-3.0). See [`LICENSE.txt`](LICENSE.txt).

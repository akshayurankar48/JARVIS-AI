# JARVIS AI (JARVIS) — Claude Code Project Guide

## Project Overview

- **Name**: JARVIS AI
- **Type**: WordPress Plugin (PHP + React)
- **Slug**: `jarvis-ai` | **Text domain**: `jarvis-ai`
- **Namespace**: `JarvisAI\` | **Prefix**: `JARVIS_AI_`
- **Repo**: https://github.com/akshayurankar48/JARVIS-AI
- **Min PHP**: 7.4 | **Min WP**: 6.0

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 7.4+, WordPress Plugin API, REST API |
| Frontend | React 18, @wordpress/scripts, @wordpress/data (Redux) |
| UI | Gutenberg PluginSidebar, @emotion/css (editor), Tailwind + Force UI (admin) |
| AI | OpenRouter API (BYOK), Plan-Confirm-Execute workflow |
| Patterns | JSON pattern library (93 patterns, 17 blueprints) |
| Testing | PHPUnit 9.6, Jest (via wp-scripts) |
| Linting | PHPCS (WordPress standard), ESLint, Prettier, Stylelint |
| CI | GitHub Actions (.github/workflows/code-analysis.yml) |

## Commands

### PHP
```bash
composer format          # PHPCBF auto-fix
composer lint            # PHPCS check
composer test            # PHPUnit tests
composer phpstan         # Static analysis
```

### JavaScript
```bash
npm start                # Dev build with watch
npm run build            # Production build
npm run lint-js          # ESLint check
npm run lint-js:fix      # ESLint auto-fix
npm run lint-css         # Stylelint check
npm run pretty:fix       # Prettier auto-fix
```

### WP-CLI (Local Sites)
```bash
PHP="/Users/bsf/Library/Application Support/Local/lightning-services/php-7.4.30+6/bin/darwin-arm64/bin/php"
SOCK="/Users/bsf/Library/Application Support/Local/run/GX2ZAwSrZ/mysql/mysqld.sock"
WP_PATH="/Users/bsf/Local Sites/wp-agent/app/public"
"$PHP" -d "mysqli.default_socket=$SOCK" /tmp/wp-cli.phar --path="$WP_PATH" <command>
```

## Architecture

```
jarvis-ai/
  jarvis-ai.php              # Plugin entry point, constants
  plugin-loader.php         # Autoloader, hooks, action registration
  actions/                  # AI-callable actions (Action_Interface)
  ai/                       # Prompt builder, orchestrator, streaming
  core/                     # Database, settings, context collector
  rest/                     # REST API controllers
  admin/                    # Admin pages, assets manager
  patterns/                 # Pattern manager + JSON library
    library/                # 24 category dirs + blueprints/
  integrations/             # WP Abilities Bridge, MCP adapter
  lib/                      # Bundled SDK, providers
  src/                      # React frontend (editor sidebar + admin)
```

## Coding Standards

### PHP
- **PHPCS**: WordPress coding standard (tabs, Yoda conditions, WPCS naming)
- **Autoloader**: PSR-4 style in `plugin-loader.php` (NOT class-prefixed filenames)
- **File naming**: `kebab-case.php` (matches namespace path, not `class-` prefixed)
- **Sanitize all input**: `sanitize_text_field()`, `sanitize_key()`, `absint()`, `esc_url_raw()`
- **Escape all output**: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **Nonces**: Required on all REST endpoints and form submissions
- **i18n**: All user-facing strings use `__()` / `_e()` with `'jarvis-ai'` text domain
- **Doc comments**: PHPDoc on all public/private methods with `@since`, `@param`, `@return`

### JavaScript / React
- **Build**: `@wordpress/scripts` (webpack)
- **State**: `@wordpress/data` with `createReduxStore`
- **Styling**: `@emotion/css` in editor sidebar (NOT Tailwind — portals break it)
- **Imports**: Use `@wordpress/*` packages, not raw React for WP integration

## Key Patterns & Gotchas

- **AI class autocorrect**: AI invents CSS class names; `autocorrect_animation_classes()` in `insert-blocks.php` maps them to valid `wpa-*` classes
- **Animation assets**: Only load when `post_content` contains `"wpa-"` substring
- **Tailwind on portals**: Doesn't apply on body-level portals — use Emotion CSS or inline styles
- **Block markup format**: `<!-- wp:blockname {"attrs"} -->\nHTML\n<!-- /wp:blockname -->`
- **Page templates**: `wp_update_post` validates `_wp_page_template` — "blank" fails if theme lacks it
- **Nonce scope**: `jarvisAiData` nonce works in admin but NOT on frontend pages
- **Force UI**: Requires `--legacy-peer-deps` for npm install
- **`plugins_api()`**: Returns **arrays** not objects — always cast with `(array)`
- **WP-CLI eval**: `global` keyword doesn't work in eval-file — use singletons directly

## Current Focus

Branch: `quality/wp-org-readiness`
- PHPCS compliance
- Security hardening
- Design intelligence (patterns, animations, prompt builder)
- Reference site analyzer enhancements

## Security Rules

- Never trust `$_GET`, `$_POST`, `$_REQUEST` — always sanitize
- Use `wp_safe_remote_get()` for external URLs (SSRF protection)
- Capability checks (`current_user_can()`) before every action
- File operations: validate paths, use `wp_delete_file()`, never `unlink()` directly
- SQL: Always use `$wpdb->prepare()` for dynamic queries
- Export files: Schedule cleanup via `wp_schedule_single_event()`

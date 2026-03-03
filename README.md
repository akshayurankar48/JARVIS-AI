# JARVIS AI

AI-powered admin assistant for WordPress. Chat with your site using natural language in the Gutenberg editor sidebar.

## What It Does

JARVIS AI is an autonomous AI agent that **operates** WordPress from natural language. Unlike simple chatbots, JARVIS plans multi-step tasks, confirms destructive actions, and executes them — all from a sidebar chat in the block editor.

- **Create & edit posts/pages** — full block-based layouts with styling
- **Manage plugins** — install, activate, deactivate, update, delete
- **Edit site design** — global styles, template parts, custom CSS, patterns
- **Generate content** — AI-written text, images (via DALL-E), full page builds
- **Manage users** — create, list, modify roles
- **SEO & accessibility** — sitemaps, accessibility audits, meta management
- **WooCommerce** — products, orders, coupons, inventory, shipping, analytics
- **60+ autonomous actions** — all available as natural language commands

## Requirements

- WordPress 6.4+
- PHP 7.4+
- Node.js 18+ (for development)
- An AI provider API key (OpenRouter, Anthropic, OpenAI, or Google)

## Installation

1. Upload the `jarvis-ai` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Go to **JARVIS AI > Settings** and enter your API key
4. Open the block editor on any post or page
5. Click the JARVIS icon in the editor toolbar to open the sidebar
6. Start chatting — e.g., *"Create a landing page for a coffee shop"*

## AI Providers

JARVIS uses a BYOK (Bring Your Own Key) model. Supported providers:

| Provider | Models | Setup |
|----------|--------|-------|
| **OpenRouter** | 100+ models (GPT-4o, Claude, Gemini, DeepSeek, etc.) | Single API key for all models |
| **Anthropic** | Claude Sonnet, Haiku | Direct API key |
| **OpenAI** | GPT-4o, GPT-4o-mini | Direct API key |
| **Google** | Gemini 2.0 Flash | Direct API key |

API keys are encrypted at rest (AES-256-CBC) and never sent anywhere except the provider you choose.

## Architecture

```
jarvis-ai/
  jarvis-ai.php           # Plugin entry, constants, activation hooks
  plugin-loader.php        # Autoloader, hooks, action registration
  actions/                 # 69 AI-callable actions (Action_Interface)
  ai/                      # Prompt builder, orchestrator, streaming, rate limiter
  core/                    # Database, settings, context collector, checkpoints
  rest/                    # REST API controllers (chat, stream, settings, history)
  admin/                   # Admin pages, menu, assets manager
  patterns/                # Pattern manager + JSON library (93 patterns, 17 blueprints)
  integrations/            # WP Abilities bridge, MCP server adapter
  lib/                     # Bundled SDK, provider clients
  src/                     # React frontend (editor sidebar + admin dashboard)
  assets/                  # CSS animations, JS utilities
```

### Key Design Decisions

- **Plan-Confirm-Execute** — the agent plans workflows, asks confirmation on destructive actions, then executes autonomously
- **Streaming SSE** — real-time token-by-token output via Server-Sent Events
- **Tool loop** — up to 25 iterations of tool calls per request with automatic retry and model fallback
- **Conversation persistence** — custom DB tables for conversations, messages, and rollback checkpoints
- **Pattern library** — 93 block patterns + 17 full-page blueprints injected into the system prompt for design-aware generation

## Development

### Setup

```bash
npm install --legacy-peer-deps
composer install
```

### Build Commands

```bash
# JavaScript / React
npm start              # Dev build with watch
npm run build          # Production build
npm run lint-js        # ESLint check
npm run lint-js:fix    # ESLint auto-fix
npm run lint-css       # Stylelint check
npm run pretty:fix     # Prettier auto-fix

# PHP
composer lint           # PHPCS check (WordPress standard)
composer format         # PHPCBF auto-fix
composer test           # PHPUnit tests

# Release
npx grunt release      # Build distributable ZIP
```

### Release ZIP

`npx grunt release` produces `jarvis-ai-{version}.zip` — a clean distributable excluding dev files (node_modules, src, tests, .claude, docs).

## REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/jarvis-ai/v1/chat` | Send a message (synchronous) |
| `POST` | `/jarvis-ai/v1/stream` | Send a message (SSE streaming) |
| `GET` | `/jarvis-ai/v1/history` | List conversations (paginated) |
| `GET` | `/jarvis-ai/v1/history/{id}` | Get conversation with messages |
| `DELETE` | `/jarvis-ai/v1/history/{id}` | Delete a conversation |
| `POST` | `/jarvis-ai/v1/history/bulk-delete` | Bulk delete conversations |
| `POST` | `/jarvis-ai/v1/history/{id}/rename` | Rename a conversation |
| `GET/POST` | `/jarvis-ai/v1/settings` | Read/update plugin settings |
| `GET` | `/jarvis-ai/v1/stats` | Usage statistics |

All endpoints require `edit_posts` capability and nonce authentication. Settings endpoints require `manage_options`.

## Security

- API keys encrypted at rest (AES-256-CBC)
- Nonce + capability checks on all REST endpoints
- Per-user rate limiting (configurable)
- All SQL queries use `$wpdb->prepare()`
- Input sanitized via `sanitize_text_field()`, `absint()`, etc.
- Output escaped via `esc_html()`, `wp_kses_post()`
- Tool actions enforce individual capability checks (e.g., `edit_users` for user management)

## Known Limitations

- System prompt is large (~8K+ tokens) due to the pattern library — increases per-request token cost
- Rate limiting is in-memory (not distributed) — won't scale across multiple servers without Redis
- The editor sidebar is limited to ~280px width
- Animation assets only load when post content contains `wpa-` classes

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

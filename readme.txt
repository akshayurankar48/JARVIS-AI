=== JARVIS AI ===
Contributors: flavor flavor
Tags: ai, assistant, agent, chatbot, gutenberg
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered admin assistant for WordPress. Chat with your site using natural language in the Gutenberg editor sidebar.

== Description ==

JARVIS AI (JARVIS) is an autonomous AI agent that **operates** WordPress from natural language. Unlike simple chatbots, JARVIS plans multi-step tasks, confirms destructive actions, and executes them — all from a sidebar chat in the block editor.

= What It Can Do =

* **Create & edit posts/pages** — including full block-based layouts with styling
* **Manage plugins** — install, activate, deactivate
* **Edit site design** — global styles, template parts, custom CSS, patterns
* **Generate content** — AI-written text, images (via DALL-E), full sites
* **Manage users** — create, list, modify roles
* **Database operations** — optimize tables, bulk find-replace
* **SEO & accessibility** — generate sitemaps, run accessibility audits
* **Import/export** — media, content, full site exports
* **And 60+ more actions** — all available as chat commands

= AI Architecture =

* **Plan-Confirm-Execute** — the agent plans multi-step workflows, asks for confirmation on destructive actions, then executes autonomously
* **BYOK (Bring Your Own Key)** — connect your own API key. Supports:
  * OpenRouter (100+ models)
  * Anthropic Claude (direct)
  * OpenAI GPT (direct)
  * Google Gemini (direct)
* **Streaming responses** — real-time token-by-token output
* **Conversation memory** — persistent chat history with branching

= Ecosystem Integration =

* **WordPress Abilities** (WP 6.9+) — all 69 actions registered as WordPress Abilities for core AI features
* **MCP Server** — all actions exposed as Model Context Protocol tools for Claude Desktop and other MCP clients

= Technical Highlights =

* Zero external PHP dependencies (bundled SDK)
* PSR-4 style autoloading
* WPCS-compliant codebase
* Custom database tables for conversations, messages, and checkpoints
* Rollback system with entity snapshots

== Installation ==

1. Upload the `jarvis-ai` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **JARVIS AI > Settings** and enter your API key (OpenRouter, Anthropic, OpenAI, or Google)
4. Open the block editor on any post or page
5. Click the JARVIS icon in the editor toolbar to open the sidebar
6. Start chatting — e.g., "Create a landing page for a coffee shop"

== Frequently Asked Questions ==

= Which AI provider should I use? =

We recommend **Anthropic Claude** (claude-sonnet-4) for the best results with WordPress tasks. OpenRouter gives access to 100+ models if you want to experiment.

= Is my API key stored securely? =

Yes. API keys are stored in the WordPress options table and never sent to any server except the AI provider you choose. All communication happens server-side via PHP.

= Can JARVIS break my site? =

JARVIS uses a Plan-Confirm-Execute model. Destructive actions (deleting posts, deactivating plugins) require your explicit confirmation. A rollback/checkpoint system can undo changes.

= Does it work with any theme? =

Yes. JARVIS AI works with any block theme and generates standard WordPress blocks. It does not depend on any specific theme.

== Changelog ==

= 1.0.0 =
* Initial release
* 69 autonomous actions (posts, plugins, users, design, media, SEO, database)
* Gutenberg sidebar chat with streaming AI responses
* Plan-Confirm-Execute agentic workflow with rollback checkpoints
* Multi-provider support: OpenRouter, Anthropic, OpenAI, Google
* Native streaming for all direct providers
* WordPress Abilities integration (WP 6.9+)
* MCP Server for Claude Desktop integration
* Admin dashboard with conversation history
* Settings UI with per-provider API key management and verification

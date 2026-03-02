<?php
/**
 * Bundled library autoloader.
 *
 * Conditionally loads the PHP AI Client SDK, provider packages, and MCP Adapter
 * only when the core WordPress equivalents are NOT already active.
 * Forward-compatible with WP 7.0 — when core ships the SDK, we defer to it.
 *
 * @package JarvisAI
 * @since 1.0.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register PSR-4 autoloader for a given namespace prefix and base directory.
 *
 * @param string $prefix    Namespace prefix (with trailing backslash).
 * @param string $base_dir  Base directory (with trailing slash).
 */
function jarvis_ai_register_psr4_autoloader( string $prefix, string $base_dir ): void {
	spl_autoload_register(
		static function ( string $class ) use ( $prefix, $base_dir ): void {
			$len = strlen( $prefix );

			if ( strncmp( $class, $prefix, $len ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

$lib_dir = __DIR__ . '/';

// ---------------------------------------------------------------------------
// 1. PHP AI Client SDK — only if WordPress core hasn't loaded it.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WordPress\\AiClient\\AiClient' ) ) {
	jarvis_ai_register_psr4_autoloader(
		'WordPress\\AiClient\\',
		$lib_dir . 'php-ai-client/src/'
	);

	// Load polyfills for older PHP versions.
	$polyfills = $lib_dir . 'php-ai-client/src/polyfills.php';
	if ( file_exists( $polyfills ) ) {
		require_once $polyfills;
	}
}

// ---------------------------------------------------------------------------
// 2. Provider packages — only if not already registered by standalone plugins.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WordPress\\AnthropicAiProvider\\Provider\\AnthropicProvider' ) ) {
	jarvis_ai_register_psr4_autoloader(
		'WordPress\\AnthropicAiProvider\\',
		$lib_dir . 'providers/anthropic/src/'
	);
}

if ( ! class_exists( 'WordPress\\OpenAiAiProvider\\Provider\\OpenAiProvider' ) ) {
	jarvis_ai_register_psr4_autoloader(
		'WordPress\\OpenAiAiProvider\\',
		$lib_dir . 'providers/openai/src/'
	);
}

if ( ! class_exists( 'WordPress\\GoogleAiProvider\\Provider\\GoogleProvider' ) ) {
	jarvis_ai_register_psr4_autoloader(
		'WordPress\\GoogleAiProvider\\',
		$lib_dir . 'providers/google/src/'
	);
}

// ---------------------------------------------------------------------------
// 3. Built-in provider implementations from the SDK itself.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WordPress\\AiClient\\ProviderImplementations\\Anthropic\\AnthropicProvider' ) ) {
	// Already covered by the main SDK autoloader above, but register explicitly
	// if the SDK was loaded from core without the implementation directory.
	$sdk_implementations = $lib_dir . 'php-ai-client/src/ProviderImplementations/';
	if ( is_dir( $sdk_implementations ) ) {
		jarvis_ai_register_psr4_autoloader(
			'WordPress\\AiClient\\ProviderImplementations\\',
			$sdk_implementations
		);
	}
}

// ---------------------------------------------------------------------------
// 4. MCP Adapter — only if WP core MCP Adapter is NOT active.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WP\\MCP\\Core\\McpAdapter' ) ) {
	jarvis_ai_register_psr4_autoloader(
		'WP\\MCP\\',
		$lib_dir . 'mcp-adapter/includes/'
	);
}

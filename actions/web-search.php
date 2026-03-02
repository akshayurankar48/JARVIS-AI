<?php
/**
 * Web Search Action.
 *
 * Performs web searches via the Tavily API to gather research data,
 * competitor insights, and current information. Returns AI-optimized
 * search results with extracted text and relevance scores.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

use WPAgent\AI\Open_Router_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Class Web_Search
 *
 * @since 1.1.0
 */
class Web_Search implements Action_Interface {

	/**
	 * Tavily search API endpoint.
	 *
	 * @var string
	 */
	const API_ENDPOINT = 'https://api.tavily.com/search';

	/**
	 * Option key for the encrypted Tavily API key.
	 *
	 * @var string
	 */
	const API_KEY_OPTION = 'wp_agent_tavily_api_key';

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 30;

	/**
	 * Maximum number of results.
	 *
	 * @var int
	 */
	const MAX_RESULTS = 10;

	/**
	 * Maximum total content length across all results (characters).
	 *
	 * @var int
	 */
	const MAX_CONTENT_LENGTH = 8000;

	/**
	 * Maximum query length in characters.
	 *
	 * @var int
	 */
	const MAX_QUERY_LENGTH = 400;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'web_search';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Search the web for current information, research topics, competitor analysis, or reference content. '
			. 'Returns AI-optimized results with extracted text and relevance scores. '
			. 'Use this before building pages to research trends, find examples, or gather data. '
			. 'Use read_url to get full content from any result URL.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'query'           => array(
					'type'        => 'string',
					'description' => 'The search query. Be specific for better results.',
				),
				'search_depth'    => array(
					'type'        => 'string',
					'enum'        => array( 'basic', 'advanced' ),
					'description' => 'Search depth. "basic" is faster, "advanced" extracts more content from each result. Defaults to "basic".',
				),
				'max_results'     => array(
					'type'        => 'integer',
					'description' => 'Number of results to return (1-10). Defaults to 5.',
				),
				'include_domains' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Only search these domains (e.g. ["wordpress.org", "developer.mozilla.org"]).',
				),
				'exclude_domains' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Skip these domains from results.',
				),
				'topic'           => array(
					'type'        => 'string',
					'enum'        => array( 'general', 'news' ),
					'description' => 'Search category. "news" focuses on recent articles. Defaults to "general".',
				),
			),
			'required'   => array( 'query' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'edit_posts';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return false;
	}

	/**
	 * Execute the action.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		// Get the Tavily API key.
		$api_key = $this->get_api_key();

		if ( is_wp_error( $api_key ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $api_key->get_error_message(),
			);
		}

		// Validate and sanitize query.
		$query = $this->validate_query( $params['query'] ?? '' );

		if ( is_wp_error( $query ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $query->get_error_message(),
			);
		}

		// Build request body.
		$body = $this->build_request_body( $query, $params );

		// Make the API request.
		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Web search request failed: %s', 'wp-agent' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( $response_code < 200 || $response_code >= 300 ) {
			$error_message = ! empty( $data['detail'] ) ? $data['detail'] : "HTTP $response_code";

			if ( 401 === $response_code || 403 === $response_code ) {
				$error_message = __( 'Invalid Tavily API key. Please check your key in WP Agent > Settings.', 'wp-agent' );
			}

			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error detail */
					__( 'Web search failed: %s', 'wp-agent' ),
					$error_message
				),
			);
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Web search returned an invalid response.', 'wp-agent' ),
			);
		}

		// Parse and format the results.
		$results      = $this->parse_results( $data );
		$result_count = count( $results );

		return array(
			'success' => true,
			'data'    => array(
				'query'        => $query,
				'results'      => $results,
				'result_count' => $result_count,
			),
			'message' => $result_count > 0
				? sprintf(
					/* translators: 1: result count, 2: search query */
					__( 'Found %1$d results for "%2$s". Use read_url to get full content from any result.', 'wp-agent' ),
					$result_count,
					$query
				)
				: sprintf(
					/* translators: %s: search query */
					__( 'No results found for "%s". Try a different query.', 'wp-agent' ),
					$query
				),
		);
	}

	/**
	 * Get the decrypted Tavily API key.
	 *
	 * @since 1.1.0
	 * @return string|\WP_Error The API key or error if not configured.
	 */
	private function get_api_key() {
		$encrypted = get_option( self::API_KEY_OPTION, '' );

		if ( empty( $encrypted ) ) {
			return new \WP_Error(
				'missing_tavily_key',
				__( 'Web search requires a Tavily API key. Add one in WP Agent > Settings.', 'wp-agent' )
			);
		}

		$api_key = Open_Router_Client::decrypt_api_key( $encrypted );

		if ( false === $api_key || empty( $api_key ) ) {
			return new \WP_Error(
				'decryption_failed',
				__( 'Failed to decrypt the Tavily API key. Try re-saving it in Settings.', 'wp-agent' )
			);
		}

		return $api_key;
	}

	/**
	 * Validate and sanitize the search query.
	 *
	 * @since 1.1.0
	 *
	 * @param string $query Raw query input.
	 * @return string|\WP_Error Sanitized query or error.
	 */
	private function validate_query( $query ) {
		$query = trim( sanitize_text_field( $query ) );

		if ( empty( $query ) ) {
			return new \WP_Error(
				'empty_query',
				__( 'Search query is required.', 'wp-agent' )
			);
		}

		if ( strlen( $query ) > self::MAX_QUERY_LENGTH ) {
			$query = substr( $query, 0, self::MAX_QUERY_LENGTH );
		}

		return $query;
	}

	/**
	 * Build the Tavily API request body.
	 *
	 * @since 1.1.0
	 *
	 * @param string $query  Sanitized query.
	 * @param array  $params Action parameters.
	 * @return array Request body.
	 */
	private function build_request_body( $query, $params ) {
		$search_depth = ! empty( $params['search_depth'] ) && 'advanced' === $params['search_depth']
			? 'advanced'
			: 'basic';

		$max_results = ! empty( $params['max_results'] )
			? min( max( (int) $params['max_results'], 1 ), self::MAX_RESULTS )
			: 5;

		$topic = ! empty( $params['topic'] ) && 'news' === $params['topic']
			? 'news'
			: 'general';

		$body = array(
			'query'        => $query,
			'search_depth' => $search_depth,
			'max_results'  => $max_results,
			'topic'        => $topic,
		);

		// Domain filters.
		if ( ! empty( $params['include_domains'] ) && is_array( $params['include_domains'] ) ) {
			$body['include_domains'] = array_map( 'sanitize_text_field', array_slice( $params['include_domains'], 0, 10 ) );
		}

		if ( ! empty( $params['exclude_domains'] ) && is_array( $params['exclude_domains'] ) ) {
			$body['exclude_domains'] = array_map( 'sanitize_text_field', array_slice( $params['exclude_domains'], 0, 10 ) );
		}

		return $body;
	}

	/**
	 * Parse Tavily API response into structured results.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data Raw API response data.
	 * @return array Parsed results.
	 */
	private function parse_results( $data ) {
		if ( empty( $data['results'] ) || ! is_array( $data['results'] ) ) {
			return array();
		}

		$results       = array();
		$total_content = 0;

		foreach ( $data['results'] as $item ) {
			$title   = ! empty( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$url     = ! empty( $item['url'] ) ? esc_url( $item['url'] ) : '';
			$content = ! empty( $item['content'] ) ? sanitize_textarea_field( $item['content'] ) : '';
			$score   = ! empty( $item['score'] ) ? round( (float) $item['score'], 3 ) : 0;

			if ( empty( $url ) ) {
				continue;
			}

			// Enforce total content budget.
			$remaining = self::MAX_CONTENT_LENGTH - $total_content;

			if ( $remaining <= 0 ) {
				// Still include the result but without content.
				$content = '[Content truncated — use read_url for full text]';
			} elseif ( strlen( $content ) > $remaining ) {
				$content       = substr( $content, 0, $remaining ) . '...';
				$total_content = self::MAX_CONTENT_LENGTH;
			} else {
				$total_content += strlen( $content );
			}

			$results[] = array(
				'title'   => $title,
				'url'     => $url,
				'content' => $content,
				'score'   => $score,
			);
		}

		return $results;
	}
}

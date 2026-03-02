<?php
/**
 * AI Pulse REST Controller.
 *
 * Aggregates AI industry RSS feeds into a single endpoint
 * for the admin dashboard news feed.
 *
 * @package JarvisAI\REST
 * @since   1.2.0
 */

namespace JarvisAI\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Class AI_Pulse_Controller
 *
 * Provides AI news feed aggregation via REST API.
 *
 * @since 1.2.0
 */
class AI_Pulse_Controller extends \WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'jarvis-ai/v1';

	/**
	 * REST route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ai-pulse';

	/**
	 * Transient key for cached feed data.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'jarvis_ai_ai_pulse_feed';

	/**
	 * Cache TTL in seconds (12 hours).
	 *
	 * @var int
	 */
	const CACHE_TTL = 43200;

	/**
	 * Maximum items per feed source.
	 *
	 * @var int
	 */
	const MAX_ITEMS_PER_FEED = 3;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 10;

	/**
	 * RSS feed sources.
	 *
	 * @since 1.2.0
	 * @return array Feed source definitions.
	 */
	private function get_feed_sources() {
		return array(
			array(
				'name' => 'OpenAI',
				'url'  => 'https://openai.com/blog/rss.xml',
				'type' => 'blog',
				'icon' => 'openai',
			),
			array(
				'name' => 'Anthropic',
				'url'  => 'https://www.anthropic.com/rss.xml',
				'type' => 'blog',
				'icon' => 'anthropic',
			),
			array(
				'name' => 'The Verge AI',
				'url'  => 'https://www.theverge.com/rss/ai-artificial-intelligence/index.xml',
				'type' => 'news',
				'icon' => 'verge',
			),
			array(
				'name' => 'TechCrunch AI',
				'url'  => 'https://techcrunch.com/category/artificial-intelligence/feed/',
				'type' => 'news',
				'icon' => 'techcrunch',
			),
			// YouTube AI channels (RSS via channel_id).
			array(
				'name' => 'Matt Wolfe',
				'url'  => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCJMQpUf_Fug4q2M0iIlgyoA',
				'type' => 'video',
				'icon' => 'youtube',
			),
			array(
				'name' => 'AI Explained',
				'url'  => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCNJ1Ymd5yFuUPtn21xtRbbw',
				'type' => 'video',
				'icon' => 'youtube',
			),
			array(
				'name' => 'Fireship',
				'url'  => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCsBjURrPoezykLs9EqgamOA',
				'type' => 'video',
				'icon' => 'youtube',
			),
		);
	}

	/**
	 * Register REST routes for AI Pulse feed.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_feed' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(
						'refresh' => array(
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * GET /jarvis-ai/v1/ai-pulse
	 *
	 * Returns aggregated AI news feed items.
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_feed( $request ) {
		$force_refresh = $request->get_param( 'refresh' );

		if ( ! $force_refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return rest_ensure_response( $cached );
			}
		}

		$items = $this->fetch_all_feeds();

		// Sort by published date descending.
		usort(
			$items,
			function ( $a, $b ) {
				return strtotime( $b['published'] ) - strtotime( $a['published'] );
			}
		);

		// Limit to 20 total items.
		$items = array_slice( $items, 0, 20 );

		$response = array(
			'items'      => $items,
			'fetched_at' => gmdate( 'c' ),
			'sources'    => count( $this->get_feed_sources() ),
		);

		set_transient( self::CACHE_KEY, $response, self::CACHE_TTL );

		return rest_ensure_response( $response );
	}

	/**
	 * Fetch and parse all RSS feed sources.
	 *
	 * @since 1.2.0
	 * @return array Aggregated feed items.
	 */
	private function fetch_all_feeds() {
		$all_items = array();

		foreach ( $this->get_feed_sources() as $source ) {
			$items = $this->fetch_single_feed( $source );
			$all_items = array_merge( $all_items, $items );
		}

		return $all_items;
	}

	/**
	 * Fetch and parse a single RSS feed.
	 *
	 * @since 1.2.0
	 *
	 * @param array $source Feed source definition.
	 * @return array Parsed feed items.
	 */
	private function fetch_single_feed( array $source ) {
		$response = wp_safe_remote_get(
			$source['url'],
			array(
				'timeout'    => self::REQUEST_TIMEOUT,
				'user-agent' => 'JARVIS-AI/1.0 (WordPress)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 400 ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return array();
		}

		return $this->parse_rss( $body, $source );
	}

	/**
	 * Parse RSS/Atom XML into structured items.
	 *
	 * @since 1.2.0
	 *
	 * @param string $xml    Raw XML content.
	 * @param array  $source Feed source definition.
	 * @return array Parsed items.
	 */
	private function parse_rss( $xml, array $source ) {
		// Suppress libxml errors for malformed feeds.
		$prev   = libxml_use_internal_errors( true );
		$parsed = simplexml_load_string( $xml );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		if ( false === $parsed ) {
			return array();
		}

		$items = array();

		// RSS 2.0 format.
		if ( isset( $parsed->channel->item ) ) {
			foreach ( array_slice( iterator_to_array( $parsed->channel->item ), 0, self::MAX_ITEMS_PER_FEED ) as $item ) {
				$items[] = $this->format_item( $item, $source, 'rss' );
			}
		}

		// Atom format.
		if ( empty( $items ) && isset( $parsed->entry ) ) {
			foreach ( array_slice( iterator_to_array( $parsed->entry ), 0, self::MAX_ITEMS_PER_FEED ) as $entry ) {
				$items[] = $this->format_item( $entry, $source, 'atom' );
			}
		}

		return array_filter( $items );
	}

	/**
	 * Format a single feed item into a standard structure.
	 *
	 * @since 1.2.0
	 *
	 * @param \SimpleXMLElement $item   Feed item.
	 * @param array             $source Feed source definition.
	 * @param string            $format Feed format ('rss' or 'atom').
	 * @return array|null Formatted item or null if invalid.
	 */
	private function format_item( $item, array $source, $format ) {
		$thumbnail = '';
		$video_id  = '';

		if ( 'atom' === $format ) {
			$title     = (string) $item->title;
			$link      = '';
			$published = (string) ( $item->published ?: $item->updated );
			$summary   = (string) $item->summary;

			if ( isset( $item->link ) ) {
				foreach ( $item->link as $link_el ) {
					$attrs = $link_el->attributes();
					if ( $attrs && 'alternate' === (string) $attrs->rel ) {
						$link = (string) $attrs->href;
						break;
					}
				}
				if ( empty( $link ) ) {
					$attrs = $item->link->attributes();
					$link  = $attrs ? (string) $attrs->href : (string) $item->link;
				}
			}

			// YouTube Atom feeds include yt:videoId in the yt namespace.
			$yt_ns = $item->children( 'yt', true );
			if ( isset( $yt_ns->videoId ) ) {
				$video_id  = (string) $yt_ns->videoId;
				$thumbnail = 'https://i.ytimg.com/vi/' . sanitize_text_field( $video_id ) . '/mqdefault.jpg';
				if ( empty( $link ) ) {
					$link = 'https://www.youtube.com/watch?v=' . sanitize_text_field( $video_id );
				}
			}

			// YouTube feeds also have media:group with media:description.
			if ( empty( $summary ) ) {
				$media_ns = $item->children( 'media', true );
				if ( isset( $media_ns->group->description ) ) {
					$summary = (string) $media_ns->group->description;
				}
			}
		} else {
			$title     = (string) $item->title;
			$link      = (string) $item->link;
			$published = (string) $item->pubDate;
			$summary   = (string) ( $item->description ?? '' );
		}

		if ( empty( $title ) || empty( $link ) ) {
			return null;
		}

		// Clean up summary — strip tags and limit length.
		$summary = wp_strip_all_tags( $summary );
		$summary = wp_trim_words( $summary, 30, '...' );

		$result = array(
			'title'     => sanitize_text_field( $title ),
			'link'      => esc_url_raw( $link ),
			'summary'   => sanitize_text_field( $summary ),
			'published' => $published ? gmdate( 'c', strtotime( $published ) ) : gmdate( 'c' ),
			'source'    => sanitize_text_field( $source['name'] ),
			'type'      => sanitize_key( $source['type'] ),
			'icon'      => sanitize_key( $source['icon'] ),
		);

		if ( ! empty( $thumbnail ) ) {
			$result['thumbnail'] = esc_url_raw( $thumbnail );
		}

		return $result;
	}
}

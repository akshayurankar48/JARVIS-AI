<?php
/**
 * Generate Content Action.
 *
 * Makes a dedicated AI call to generate long-form written content
 * (blog posts, product descriptions, ad copy, etc.) using the
 * existing OpenRouter client. Returns the generated text so the
 * orchestrator can insert it into posts via create_post or insert_blocks.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

use WPAgent\AI\Open_Router_Client;
use WPAgent\AI\Model_Router;

defined( 'ABSPATH' ) || exit;

/**
 * Class Generate_Content
 *
 * @since 1.1.0
 */
class Generate_Content implements Action_Interface {

	/**
	 * Maximum topic length in characters.
	 *
	 * @var int
	 */
	const MAX_TOPIC_LENGTH = 1000;

	/**
	 * Maximum additional instructions length.
	 *
	 * @var int
	 */
	const MAX_INSTRUCTIONS_LENGTH = 2000;

	/**
	 * Content types and their system prompt instructions.
	 *
	 * @var array<string, array{label: string, instruction: string}>
	 */
	const CONTENT_TYPES = array(
		'blog_post'           => array(
			'label'       => 'Blog Post',
			'instruction' => 'Write a well-structured blog post with a compelling introduction, clear sections with headings (use ## for H2 and ### for H3), and a conclusion. Use engaging language and include a call-to-action where appropriate.',
		),
		'product_description' => array(
			'label'       => 'Product Description',
			'instruction' => 'Write a persuasive product description that highlights key features, benefits, and value proposition. Use sensory language and address customer pain points. Include a clear call-to-action.',
		),
		'ad_copy'             => array(
			'label'       => 'Ad Copy',
			'instruction' => 'Write compelling advertising copy that grabs attention immediately, communicates the key benefit, creates urgency, and includes a strong call-to-action. Be concise and impactful.',
		),
		'social_post'         => array(
			'label'       => 'Social Media Post',
			'instruction' => 'Write an engaging social media post that captures attention quickly, encourages interaction, and fits social media best practices. Keep it concise and include relevant hashtag suggestions at the end.',
		),
		'email'               => array(
			'label'       => 'Email',
			'instruction' => 'Write a professional email with a clear subject line suggestion, engaging opening, well-organized body, and a clear call-to-action. Maintain appropriate tone throughout.',
		),
		'page_copy'           => array(
			'label'       => 'Website Page Copy',
			'instruction' => 'Write website page copy that is clear, scannable, and conversion-focused. Use short paragraphs, clear headings, bullet points where appropriate, and strong calls-to-action.',
		),
		'custom'              => array(
			'label'       => 'Custom Content',
			'instruction' => 'Write content based on the provided topic and instructions. Focus on quality, clarity, and engagement.',
		),
	);

	/**
	 * Tone presets.
	 *
	 * @var array<string, string>
	 */
	const TONES = array(
		'professional' => 'Use a professional, authoritative tone. Be clear, precise, and trustworthy.',
		'casual'       => 'Use a casual, conversational tone. Be friendly, approachable, and relatable.',
		'persuasive'   => 'Use a persuasive, compelling tone. Focus on benefits, use emotional triggers, and drive action.',
		'informative'  => 'Use an informative, educational tone. Be thorough, factual, and helpful.',
		'witty'        => 'Use a witty, clever tone. Be entertaining while still informative. Use humor naturally.',
		'empathetic'   => 'Use an empathetic, understanding tone. Show you understand the reader\'s challenges and concerns.',
	);

	/**
	 * Length presets mapped to approximate word counts.
	 *
	 * @var array<string, array{words: string, tokens: int}>
	 */
	const LENGTHS = array(
		'short'  => array(
			'words'  => '150-300 words',
			'tokens' => 2048,
		),
		'medium' => array(
			'words'  => '500-800 words',
			'tokens' => 4096,
		),
		'long'   => array(
			'words'  => '1000-1500 words',
			'tokens' => 8192,
		),
	);

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'generate_content';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Generate written content using AI (blog posts, product descriptions, ad copy, social posts, emails, page copy). '
			. 'Makes a dedicated AI writing call and returns the generated text. '
			. 'Use this for high-quality, structured content generation. '
			. 'After generating, use create_post or insert_blocks to publish the content.';
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
				'content_type'            => array(
					'type'        => 'string',
					'enum'        => array_keys( self::CONTENT_TYPES ),
					'description' => 'Type of content to generate: blog_post, product_description, ad_copy, social_post, email, page_copy, or custom.',
				),
				'topic'                   => array(
					'type'        => 'string',
					'description' => 'The topic, subject, or brief for the content. Be specific for better results.',
				),
				'tone'                    => array(
					'type'        => 'string',
					'enum'        => array_keys( self::TONES ),
					'description' => 'Writing tone: professional, casual, persuasive, informative, witty, or empathetic. Defaults to professional.',
				),
				'length'                  => array(
					'type'        => 'string',
					'enum'        => array_keys( self::LENGTHS ),
					'description' => 'Content length: short (150-300 words), medium (500-800 words), or long (1000-1500 words). Defaults to medium.',
				),
				'keywords'                => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Optional SEO keywords to naturally incorporate into the content.',
				),
				'target_audience'         => array(
					'type'        => 'string',
					'description' => 'Optional description of the target audience (e.g. "small business owners", "tech-savvy millennials").',
				),
				'additional_instructions' => array(
					'type'        => 'string',
					'description' => 'Any additional instructions or context for the content generation.',
				),
			),
			'required'   => array( 'content_type', 'topic' ),
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
	 * Content generation alone doesn't modify site state.
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
		// --- 1. Validate parameters ---------------------------------------------------------.
		$content_type = isset( $params['content_type'] ) ? sanitize_text_field( $params['content_type'] ) : '';
		$topic        = isset( $params['topic'] ) ? trim( $params['topic'] ) : '';

		if ( empty( $content_type ) || ! isset( self::CONTENT_TYPES[ $content_type ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid content type. Use: blog_post, product_description, ad_copy, social_post, email, page_copy, or custom.', 'wp-agent' ),
			);
		}

		if ( empty( $topic ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'A topic is required to generate content.', 'wp-agent' ),
			);
		}

		if ( mb_strlen( $topic ) > self::MAX_TOPIC_LENGTH ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: maximum allowed characters */
					__( 'Topic must be %d characters or fewer.', 'wp-agent' ),
					self::MAX_TOPIC_LENGTH
				),
			);
		}

		$topic = sanitize_textarea_field( $topic );

		// Optional parameters.
		$tone     = isset( $params['tone'] ) ? sanitize_text_field( $params['tone'] ) : 'professional';
		$length   = isset( $params['length'] ) ? sanitize_text_field( $params['length'] ) : 'medium';
		$keywords = array();
		$audience = '';
		$extra    = '';

		if ( ! isset( self::TONES[ $tone ] ) ) {
			$tone = 'professional';
		}
		if ( ! isset( self::LENGTHS[ $length ] ) ) {
			$length = 'medium';
		}

		if ( ! empty( $params['keywords'] ) && is_array( $params['keywords'] ) ) {
			$keywords = array_slice(
				array_values(
					array_filter(
						array_map( 'sanitize_text_field', $params['keywords'] ),
						'strlen'
					)
				),
				0,
				10
			);
		}

		if ( ! empty( $params['target_audience'] ) ) {
			$audience = sanitize_text_field( $params['target_audience'] );
		}

		if ( ! empty( $params['additional_instructions'] ) ) {
			$extra = sanitize_textarea_field( $params['additional_instructions'] );
			if ( mb_strlen( $extra ) > self::MAX_INSTRUCTIONS_LENGTH ) {
				$extra = mb_substr( $extra, 0, self::MAX_INSTRUCTIONS_LENGTH );
			}
		}

		// --- 2. Build the writing prompt ----------------------------------------------------.
		$system_prompt = $this->build_system_prompt( $content_type, $tone, $length );
		$user_prompt   = $this->build_user_prompt( $content_type, $topic, $length, $keywords, $audience, $extra );

		// --- 3. Make the AI call ------------------------------------------------------------.
		$client        = Open_Router_Client::get_instance();
		$length_config = self::LENGTHS[ $length ];

		// Use the powerful model for best writing quality.
		$models = Model_Router::get_instance()->get_available_models();
		$model  = $models[ Model_Router::TIER_POWERFUL ]['id'];

		$messages = array(
			array(
				'role'    => 'system',
				'content' => $system_prompt,
			),
			array(
				'role'    => 'user',
				'content' => $user_prompt,
			),
		);

		$response = $client->chat(
			$messages,
			$model,
			array(),          // No tools needed for content generation.
			0.7,         // Balanced creativity.
			$length_config['tokens']
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Content generation failed: %s', 'wp-agent' ),
					$response->get_error_message()
				),
			);
		}

		$content = isset( $response['content'] ) ? trim( $response['content'] ) : '';

		if ( empty( $content ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Content generation returned empty content. Please try again.', 'wp-agent' ),
			);
		}

		// --- 4. Build result ----------------------------------------------------------------.
		$type_label = self::CONTENT_TYPES[ $content_type ]['label'];
		$word_count = str_word_count( wp_strip_all_tags( $content ) );

		return array(
			'success' => true,
			'data'    => array(
				'content'      => $content,
				'content_type' => $content_type,
				'tone'         => $tone,
				'length'       => $length,
				'word_count'   => $word_count,
			),
			'message' => sprintf(
				/* translators: 1: content type label, 2: word count */
				__( 'Generated %1$s (%2$d words). Use create_post or insert_blocks to add it to the site.', 'wp-agent' ),
				$type_label,
				$word_count
			),
		);
	}

	/**
	 * Build the system prompt for the content generation call.
	 *
	 * @since 1.1.0
	 *
	 * @param string $content_type Content type key.
	 * @param string $tone         Tone key.
	 * @param string $length       Length key.
	 * @return string System prompt.
	 */
	private function build_system_prompt( $content_type, $tone, $length ) {
		$type_config   = self::CONTENT_TYPES[ $content_type ];
		$tone_desc     = self::TONES[ $tone ];
		$length_config = self::LENGTHS[ $length ];

		$prompt  = "You are an expert content writer for WordPress websites.\n\n";
		$prompt .= "## Content Type\n";
		$prompt .= $type_config['instruction'] . "\n\n";
		$prompt .= "## Tone\n";
		$prompt .= $tone_desc . "\n\n";
		$prompt .= "## Length\n";
		$prompt .= "Target length: {$length_config['words']}.\n\n";
		$prompt .= "## Rules\n";
		$prompt .= "- Write only the content itself. Do not include meta-commentary like \"Here's your blog post\" or \"I hope this helps\".\n";
		$prompt .= "- Use markdown formatting (headings, bold, lists) for structure.\n";
		$prompt .= "- Make the content engaging, original, and ready to publish.\n";
		$prompt .= "- Naturally incorporate any provided keywords without keyword stuffing.\n";
		$prompt .= "- Write for the web: short paragraphs, scannable structure, clear language.\n";

		return $prompt;
	}

	/**
	 * Build the user prompt for the content generation call.
	 *
	 * @since 1.1.0
	 *
	 * @param string $content_type Content type key.
	 * @param string $topic        Content topic/brief.
	 * @param string $length       Length key.
	 * @param array  $keywords     SEO keywords.
	 * @param string $audience     Target audience description.
	 * @param string $extra        Additional instructions.
	 * @return string User prompt.
	 */
	private function build_user_prompt( $content_type, $topic, $length, $keywords, $audience, $extra ) {
		$type_label = self::CONTENT_TYPES[ $content_type ]['label'];
		$prompt     = "Write a {$type_label} about: {$topic}";

		if ( ! empty( $keywords ) ) {
			$prompt .= "\n\nSEO Keywords to include: " . implode( ', ', $keywords );
		}

		if ( ! empty( $audience ) ) {
			$prompt .= "\n\nTarget audience: {$audience}";
		}

		if ( ! empty( $extra ) ) {
			$prompt .= "\n\nAdditional instructions: {$extra}";
		}

		return $prompt;
	}
}

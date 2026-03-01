/**
 * Welcome screen — shown when no messages exist.
 *
 * Three states:
 * 1. No API key     -> setup message + settings link.
 * 2. Has key (blank) -> build/design prompts based on post type.
 * 3. Has key (content) -> improve/extend prompts.
 *
 * @package
 * @since 1.0.0
 */

import { useMemo } from '@wordpress/element';
import { css } from '@emotion/css';
import {
	Bot,
	Settings,
	FileText,
	Pencil,
	LayoutGrid,
	Paintbrush,
	Globe,
	Search,
	Image,
	Sparkles,
	PlusCircle,
	Rocket,
	Type,
} from 'lucide-react';
import { colors, radii, spacing, fontSizes, fadeIn, focusRing } from './styles';

/* ── Styles ─────────────────────────────────────────────────────── */

const container = css`
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 0 ${ spacing.xxl };
	text-align: center;
	animation: ${ fadeIn } 0.3s ease-out;
`;

const iconWrapBase = css`
	width: 56px;
	height: 56px;
	border-radius: ${ radii.full };
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: ${ spacing.lg };
`;

const iconWrapActive = css`
	${ iconWrapBase };
	background: ${ colors.primaryLight };
	color: ${ colors.primary };
`;

const iconWrapInactive = css`
	${ iconWrapBase };
	background: ${ colors.bgSubtle };
	color: ${ colors.textMuted };
`;

const title = css`
	font-size: ${ fontSizes.base };
	font-weight: 600;
	color: ${ colors.text };
	margin-bottom: 4px;
`;

const subtitle = css`
	font-size: ${ fontSizes.xs };
	color: ${ colors.textSecondary };
	line-height: 1.5;
	margin-bottom: ${ spacing.xxl };
`;

const settingsLink = css`
	${ focusRing };
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: ${ fontSizes.xs };
	font-weight: 500;
	color: ${ colors.primary };
	text-decoration: none;
	transition: color 0.15s ease;

	&:hover {
		color: ${ colors.primaryHover };
	}
`;

const promptList = css`
	width: 100%;
	display: flex;
	flex-direction: column;
	gap: ${ spacing.sm };
`;

const promptCard = css`
	${ focusRing };
	width: 100%;
	display: flex;
	align-items: center;
	gap: ${ spacing.md };
	padding: ${ spacing.md } ${ spacing.lg };
	background: ${ colors.bg };
	border: 1px solid ${ colors.border };
	border-radius: ${ radii.md };
	cursor: pointer;
	text-align: left;
	transition: all 0.2s ease;

	&:hover {
		border-color: ${ colors.primaryLighter };
		background: ${ colors.primaryLight };
		box-shadow: 0 2px 8px ${ colors.shadow };
		transform: translateY(-1px);
	}

	&:hover svg {
		color: ${ colors.primary };
	}
`;

const promptIcon = css`
	flex-shrink: 0;
	color: ${ colors.textMuted };
	transition: color 0.2s ease;
`;

const promptLabel = css`
	font-size: ${ fontSizes.sm };
	color: ${ colors.textSecondary };
	font-weight: 450;
`;

/* ── Prompt Sets ───────────────────────────────────────────────── */

const PROMPTS_BLANK_PAGE = [
	{
		icon: Rocket,
		label: 'Build a landing page',
		message: 'Build a professional landing page with a hero section, features grid, testimonials, and a call-to-action',
	},
	{
		icon: FileText,
		label: 'Draft a blog post',
		message: 'Help me draft a blog post about',
	},
	{
		icon: LayoutGrid,
		label: 'Add a hero section',
		message: 'Add a hero section with a heading, paragraph, and call-to-action button',
	},
	{
		icon: Paintbrush,
		label: 'Design from a reference',
		message: 'I want to build a page inspired by a reference site. Let me share the URL.',
	},
];

const PROMPTS_BLANK_POST = [
	{
		icon: FileText,
		label: 'Draft a blog post',
		message: 'Help me draft a blog post about',
	},
	{
		icon: Image,
		label: 'Post with featured image',
		message: 'Create a blog post and set a relevant featured image',
	},
	{
		icon: Search,
		label: 'SEO-optimized article',
		message: 'Write an SEO-optimized blog post about',
	},
	{
		icon: LayoutGrid,
		label: 'Add content blocks',
		message: 'Add a hero section with a heading, paragraph, and call-to-action button',
	},
];

const PROMPTS_HAS_CONTENT = [
	{
		icon: Sparkles,
		label: 'Improve this content',
		message: 'Review and improve the current content — make it more engaging and polished',
	},
	{
		icon: PlusCircle,
		label: 'Add a new section',
		message: 'Add a new section to this page. What would work well with the existing content?',
	},
	{
		icon: Pencil,
		label: 'Rewrite in a different tone',
		message: 'Rewrite the current content in a more professional and engaging tone',
	},
	{
		icon: Search,
		label: 'Optimize for SEO',
		message: 'Analyze and optimize this content for search engines',
	},
];

const PROMPTS_PUBLISHED = [
	{
		icon: Sparkles,
		label: 'Refresh this content',
		message: 'This is a published post — review it and suggest updates to keep it fresh and relevant',
	},
	{
		icon: PlusCircle,
		label: 'Extend with new sections',
		message: 'Add new sections to expand this published content',
	},
	{
		icon: Type,
		label: 'Improve readability',
		message: 'Improve the readability and flow of this content while keeping the key message',
	},
	{
		icon: Globe,
		label: 'Optimize for SEO',
		message: 'Audit this published content for SEO and make improvements',
	},
];

/**
 * Pick the right prompt set based on editor context.
 *
 * @param {Object} context - From useEditorContext().
 * @return {Array} Prompt objects.
 */
function getPromptsForContext( context ) {
	if ( context.type === 'published' ) {
		return PROMPTS_PUBLISHED;
	}

	if ( context.type === 'has-content' ) {
		return PROMPTS_HAS_CONTENT;
	}

	// Blank — differentiate by post type.
	if ( context.postType === 'page' ) {
		return PROMPTS_BLANK_PAGE;
	}

	return PROMPTS_BLANK_POST;
}

/**
 * Get a contextual greeting subtitle.
 *
 * @param {Object} context - From useEditorContext().
 * @return {string} Subtitle text.
 */
function getSubtitle( context ) {
	if ( context.type === 'published' ) {
		return 'I can help refresh and improve your published content.';
	}
	if ( context.type === 'has-content' ) {
		return 'I see you have content started. Want me to help improve it?';
	}
	if ( context.postType === 'page' ) {
		return 'Let me help you build a beautiful page.';
	}
	return 'Ask me to create content, edit posts, or manage your site.';
}

/* ── Helpers ────────────────────────────────────────────────────── */

/**
 * Build a safe settings URL. Validates that adminUrl from
 * wpAgentData is same-origin; falls back to a relative path.
 */
const getSafeSettingsUrl = () => {
	const { adminUrl } = window.wpAgentData || {};
	const fallback = '/wp-admin/admin.php?page=wp-agent-settings';

	if ( ! adminUrl ) {
		return fallback;
	}

	try {
		const parsed = new URL( adminUrl, window.location.origin );
		if ( parsed.origin !== window.location.origin ) {
			return fallback;
		}
		const base = parsed.href.endsWith( '/' ) ? parsed.href : parsed.href + '/';
		return `${ base }admin.php?page=wp-agent-settings`;
	} catch {
		return fallback;
	}
};

/* ── Component ──────────────────────────────────────────────────── */

const WelcomeScreen = ( { hasApiKey, onSendMessage, editorContext } ) => {
	const prompts = useMemo(
		() => getPromptsForContext( editorContext ),
		[ editorContext.type, editorContext.postType ]
	);

	const subtitleText = useMemo(
		() => getSubtitle( editorContext ),
		[ editorContext.type, editorContext.postType ]
	);

	if ( ! hasApiKey ) {
		return (
			<div className={ container }>
				<div className={ iconWrapInactive }>
					<Bot size={ 28 } />
				</div>
				<h3 className={ title }>Set up JARVIS</h3>
				<p className={ subtitle }>
					Configure your API key to start using JARVIS.
				</p>
				<a
					href={ getSafeSettingsUrl() }
					className={ settingsLink }
					target="_blank"
					rel="noopener noreferrer"
				>
					<Settings size={ 14 } />
					Go to Settings
				</a>
			</div>
		);
	}

	return (
		<div className={ container }>
			<div className={ iconWrapActive }>
				<Bot size={ 28 } />
			</div>
			<h3 className={ title }>Hi! I&apos;m JARVIS.</h3>
			<p className={ subtitle }>{ subtitleText }</p>
			<div className={ promptList }>
				{ prompts.map( ( prompt ) => (
					<button
						key={ prompt.label }
						type="button"
						onClick={ () => onSendMessage( prompt.message ) }
						className={ promptCard }
					>
						<prompt.icon size={ 16 } className={ promptIcon } />
						<span className={ promptLabel }>{ prompt.label }</span>
					</button>
				) ) }
			</div>
		</div>
	);
};

export default WelcomeScreen;

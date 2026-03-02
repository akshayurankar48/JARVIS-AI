/**
 * Design Score Card — Instant page quality analysis.
 *
 * Analyzes the current editor blocks and shows scores for:
 * Accessibility, SEO, Structure, and Visual quality.
 * Displayed after a page build completes.
 *
 * @package
 * @since 1.2.0
 */

import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { css, keyframes } from '@emotion/css';
import { X, ChevronDown, ChevronUp, Sparkles, Send } from 'lucide-react';
import { colors, radii, spacing, fontSizes, fadeIn, focusRing } from './styles';

/* ── Styles ─────────────────────────────────────────────────────── */

const fillAnimation = keyframes`
	from { stroke-dashoffset: 251; }
`;

const container = css`
	padding: ${ spacing.md } ${ spacing.lg };
	background: linear-gradient(135deg, ${ colors.bg }, ${ colors.bgSubtle });
	border-top: 1px solid ${ colors.borderLight };
	animation: ${ fadeIn } 0.3s ease-out;
`;

const headerRow = css`
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: ${ spacing.sm };
`;

const titleStyle = css`
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: ${ fontSizes.sm };
	font-weight: 600;
	color: ${ colors.text };
`;

const closeBtn = css`
	${ focusRing };
	background: none;
	border: none;
	padding: 4px;
	cursor: pointer;
	color: ${ colors.textMuted };
	border-radius: ${ radii.sm };
	transition: color 0.15s ease;

	&:hover {
		color: ${ colors.text };
	}
`;

const scoreGrid = css`
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: ${ spacing.sm };
	margin-bottom: ${ spacing.sm };
`;

const scoreItem = css`
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px;
	padding: ${ spacing.sm };
	background: ${ colors.bg };
	border: 1px solid ${ colors.border };
	border-radius: ${ radii.md };
	transition: all 0.15s ease;
`;

const scoreLabel = css`
	font-size: 9px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	color: ${ colors.textMuted };
`;

const overallRow = css`
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: ${ spacing.xs } ${ spacing.sm };
	background: ${ colors.bg };
	border: 1px solid ${ colors.border };
	border-radius: ${ radii.md };
	margin-bottom: ${ spacing.sm };
`;

const overallLabel = css`
	font-size: ${ fontSizes.xs };
	font-weight: 600;
	color: ${ colors.text };
`;

const overallScore = css`
	font-size: ${ fontSizes.base };
	font-weight: 700;
`;

const detailsToggle = css`
	${ focusRing };
	width: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 4px;
	padding: 4px;
	background: none;
	border: none;
	font-size: 10px;
	font-weight: 500;
	color: ${ colors.textMuted };
	cursor: pointer;
	border-radius: ${ radii.sm };
	transition: color 0.15s ease;

	&:hover {
		color: ${ colors.text };
	}
`;

const detailsList = css`
	margin-top: ${ spacing.xs };
	padding: ${ spacing.sm };
	background: ${ colors.bg };
	border: 1px solid ${ colors.border };
	border-radius: ${ radii.md };
	animation: ${ fadeIn } 0.2s ease-out;
`;

const detailItem = css`
	display: flex;
	align-items: flex-start;
	gap: 6px;
	padding: 3px 0;
	font-size: 10px;
	line-height: 1.4;
	color: ${ colors.textSecondary };
`;

const improveBtnStyle = css`
	${ focusRing };
	width: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 6px;
	padding: 6px ${ spacing.md };
	margin-top: ${ spacing.sm };
	background: ${ colors.primaryLight };
	border: 1px solid ${ colors.primaryLighter };
	border-radius: ${ radii.md };
	font-size: ${ fontSizes.xs };
	font-weight: 500;
	color: ${ colors.primary };
	cursor: pointer;
	transition: all 0.15s ease;

	&:hover {
		background: ${ colors.primaryLighter };
		border-color: ${ colors.primary };
	}
`;

/* ── Score Ring SVG ─────────────────────────────────────────────── */

function ScoreRing( { score, size = 48 } ) {
	const radius = 40;
	const circumference = 2 * Math.PI * radius;
	const offset = circumference - ( score / 100 ) * circumference;

	let color = '#ef4444'; // red
	if ( score >= 80 ) {
		color = '#22c55e'; // green
	} else if ( score >= 60 ) {
		color = '#f59e0b'; // amber
	} else if ( score >= 40 ) {
		color = '#f97316'; // orange
	}

	return (
		<svg width={ size } height={ size } viewBox="0 0 100 100">
			<circle
				cx="50" cy="50" r={ radius }
				fill="none"
				stroke={ colors.borderLight }
				strokeWidth="8"
			/>
			<circle
				cx="50" cy="50" r={ radius }
				fill="none"
				stroke={ color }
				strokeWidth="8"
				strokeLinecap="round"
				strokeDasharray={ circumference }
				strokeDashoffset={ offset }
				transform="rotate(-90 50 50)"
				style={ { animation: `${ fillAnimation } 0.8s ease-out` } }
			/>
			<text
				x="50" y="50"
				textAnchor="middle"
				dominantBaseline="central"
				fill={ color }
				fontSize="22"
				fontWeight="700"
			>
				{ score }
			</text>
		</svg>
	);
}

/* ── Analysis Functions ─────────────────────────────────────────── */

function analyzeAccessibility( blocks, content ) {
	let score = 100;
	const issues = [];

	// Check heading hierarchy.
	const headings = [];
	const walkBlocks = ( list ) => {
		for ( const block of list ) {
			if ( block.name === 'core/heading' ) {
				const level = block.attributes?.level || 2;
				headings.push( level );
			}
			if ( block.innerBlocks?.length ) {
				walkBlocks( block.innerBlocks );
			}
		}
	};
	walkBlocks( blocks );

	if ( headings.length === 0 ) {
		score -= 15;
		issues.push( 'No headings found — add H1/H2 for screen readers' );
	} else {
		// Check for H1.
		if ( ! headings.includes( 1 ) ) {
			score -= 5;
			issues.push( 'No H1 heading — add one for document structure' );
		}
		// Check for skipped levels.
		const sorted = [ ...new Set( headings ) ].sort();
		for ( let i = 1; i < sorted.length; i++ ) {
			if ( sorted[ i ] - sorted[ i - 1 ] > 1 ) {
				score -= 5;
				issues.push( `Heading level skipped (H${ sorted[ i - 1 ] } to H${ sorted[ i ] })` );
				break;
			}
		}
	}

	// Check images for alt text.
	const imgCount = ( content.match( /<img/gi ) || [] ).length;
	const altCount = ( content.match( /alt="[^"]+"/gi ) || [] ).length;
	const missingAlt = imgCount - altCount;
	if ( missingAlt > 0 ) {
		score -= Math.min( missingAlt * 5, 20 );
		issues.push( `${ missingAlt } image(s) missing alt text` );
	}

	// Check for links.
	const linkCount = ( content.match( /<a /gi ) || [] ).length;
	if ( linkCount > 0 ) {
		score += 5; // Bonus for having CTAs.
	}

	// Check for ARIA landmarks.
	if ( content.includes( 'role=' ) || content.includes( 'aria-' ) ) {
		score += 5;
	}

	return { score: Math.max( 0, Math.min( 100, score ) ), issues };
}

function analyzeSEO( blocks, content ) {
	let score = 100;
	const issues = [];

	// Check for H1.
	const hasH1 = blocks.some( function checkH1( b ) {
		if ( b.name === 'core/heading' && b.attributes?.level === 1 ) {
			return true;
		}
		return b.innerBlocks?.some( checkH1 ) || false;
	} );
	if ( ! hasH1 ) {
		score -= 15;
		issues.push( 'No H1 heading — essential for SEO' );
	}

	// Check heading count.
	let headingCount = 0;
	const countHeadings = ( list ) => {
		for ( const b of list ) {
			if ( b.name === 'core/heading' ) {
				headingCount++;
			}
			if ( b.innerBlocks?.length ) {
				countHeadings( b.innerBlocks );
			}
		}
	};
	countHeadings( blocks );
	if ( headingCount < 3 ) {
		score -= 10;
		issues.push( 'Few headings — add more for content structure' );
	}

	// Check word count.
	const text = content.replace( /<[^>]+>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
	const wordCount = text.split( ' ' ).filter( Boolean ).length;
	if ( wordCount < 100 ) {
		score -= 15;
		issues.push( `Low word count (${ wordCount }) — aim for 300+` );
	} else if ( wordCount >= 300 ) {
		score += 5;
	}

	// Check for links (internal/external).
	const linkCount = ( content.match( /<a /gi ) || [] ).length;
	if ( linkCount === 0 ) {
		score -= 5;
		issues.push( 'No links found — add CTAs or internal links' );
	}

	// Check images.
	const imgCount = ( content.match( /<img/gi ) || [] ).length;
	if ( imgCount === 0 && wordCount > 200 ) {
		score -= 5;
		issues.push( 'No images — visual content improves engagement' );
	}

	return { score: Math.max( 0, Math.min( 100, score ) ), issues };
}

function analyzeStructure( blocks ) {
	let score = 100;
	const issues = [];

	const topLevelCount = blocks.length;

	// Section variety — check unique block types.
	const blockTypes = new Set();
	const countTypes = ( list ) => {
		for ( const b of list ) {
			blockTypes.add( b.name );
			if ( b.innerBlocks?.length ) {
				countTypes( b.innerBlocks );
			}
		}
	};
	countTypes( blocks );

	if ( topLevelCount < 3 ) {
		score -= 20;
		issues.push( 'Only ' + topLevelCount + ' top-level blocks — add more sections' );
	} else if ( topLevelCount >= 5 ) {
		score += 5;
	}

	if ( blockTypes.size < 3 ) {
		score -= 10;
		issues.push( 'Low block variety — mix headings, paragraphs, columns, images' );
	}

	// Check for CTA (buttons).
	const hasButton = blocks.some( function checkBtn( b ) {
		if ( b.name === 'core/button' || b.name === 'core/buttons' ) {
			return true;
		}
		return b.innerBlocks?.some( checkBtn ) || false;
	} );
	if ( ! hasButton ) {
		score -= 10;
		issues.push( 'No call-to-action buttons found' );
	}

	// Check for group/cover blocks (sections).
	const hasSections = blocks.some( ( b ) =>
		b.name === 'core/group' || b.name === 'core/cover' || b.name === 'core/columns'
	);
	if ( hasSections ) {
		score += 5;
	} else if ( topLevelCount > 3 ) {
		score -= 5;
		issues.push( 'Content not organized into sections — wrap in Groups' );
	}

	return { score: Math.max( 0, Math.min( 100, score ) ), issues };
}

function analyzeVisual( blocks, content ) {
	let score = 85; // Start at 85 and adjust.
	const issues = [];

	// Check for animations.
	const hasAnimations = content.includes( 'wpa-' );
	if ( hasAnimations ) {
		score += 10;
	} else {
		issues.push( 'No wpa- animations — add scroll effects for polish' );
	}

	// Check for color consistency (inline styles).
	const colorMatches = content.match( /#[0-9a-fA-F]{6}/g ) || [];
	const uniqueColors = new Set( colorMatches.map( ( c ) => c.toLowerCase() ) );
	if ( uniqueColors.size > 8 ) {
		score -= 10;
		issues.push( `${ uniqueColors.size } unique colors — stick to 4-6 for consistency` );
	}

	// Check for images (visual richness).
	const imgCount = ( content.match( /<img/gi ) || [] ).length;
	if ( imgCount > 0 ) {
		score += 5;
	}

	// Check for gradient/glassmorphism effects.
	if ( content.includes( 'gradient' ) || content.includes( 'backdrop-filter' ) ) {
		score += 5;
	}

	return { score: Math.max( 0, Math.min( 100, score ) ), issues };
}

/* ── Main Component ────────────────────────────────────────────── */

export default function DesignScore( { onClose, onImprove } ) {
	const [ showDetails, setShowDetails ] = useState( false );

	const { blocks, content } = useSelect( ( select ) => {
		try {
			const be = select( blockEditorStore );
			const ed = select( editorStore );
			return {
				blocks: be.getBlocks() || [],
				content: ed.getEditedPostContent() || '',
			};
		} catch {
			return { blocks: [], content: '' };
		}
	}, [] );

	const scores = useMemo( () => {
		if ( ! blocks.length ) {
			return null;
		}

		const accessibility = analyzeAccessibility( blocks, content );
		const seo = analyzeSEO( blocks, content );
		const structure = analyzeStructure( blocks );
		const visual = analyzeVisual( blocks, content );
		const overall = Math.round(
			( accessibility.score + seo.score + structure.score + visual.score ) / 4
		);

		return { accessibility, seo, structure, visual, overall };
	}, [ blocks, content ] );

	if ( ! scores ) {
		return null;
	}

	const allIssues = [
		...scores.accessibility.issues,
		...scores.seo.issues,
		...scores.structure.issues,
		...scores.visual.issues,
	];

	const getOverallColor = ( score ) => {
		if ( score >= 80 ) {
			return '#22c55e';
		}
		if ( score >= 60 ) {
			return '#f59e0b';
		}
		return '#ef4444';
	};

	const getOverallText = ( score ) => {
		if ( score >= 90 ) {
			return 'Excellent! Production ready.';
		}
		if ( score >= 80 ) {
			return 'Great quality. Minor tweaks possible.';
		}
		if ( score >= 60 ) {
			return 'Good start. Some improvements needed.';
		}
		return 'Needs work. See suggestions below.';
	};

	const handleImprove = useCallback( () => {
		const lowScores = [];
		if ( scores.accessibility.score < 80 ) {
			lowScores.push( 'accessibility (heading hierarchy, alt text)' );
		}
		if ( scores.seo.score < 80 ) {
			lowScores.push( 'SEO (headings, word count, links)' );
		}
		if ( scores.structure.score < 80 ) {
			lowScores.push( 'structure (more sections, CTA buttons)' );
		}
		if ( scores.visual.score < 80 ) {
			lowScores.push( 'visual quality (animations, color consistency)' );
		}

		const prompt = lowScores.length > 0
			? `Improve this page's ${ lowScores.join( ' and ' ) }. The Design Score found these issues: ${ allIssues.slice( 0, 5 ).join( '; ' ) }`
			: 'The page looks great! Add some final polish — maybe more animations or a testimonials section.';

		if ( onImprove ) {
			onImprove( prompt );
		}
	}, [ scores, allIssues, onImprove ] );

	return (
		<div className={ container }>
			<div className={ headerRow }>
				<div className={ titleStyle }>
					<Sparkles size={ 14 } />
					Design Score
				</div>
				<button
					type="button"
					onClick={ onClose }
					className={ closeBtn }
					aria-label="Close design score"
				>
					<X size={ 14 } />
				</button>
			</div>

			{ /* Overall score */ }
			<div className={ overallRow }>
				<div>
					<div className={ overallLabel }>Overall Score</div>
					<div style={ { fontSize: '10px', color: colors.textMuted } }>
						{ getOverallText( scores.overall ) }
					</div>
				</div>
				<div className={ overallScore } style={ { color: getOverallColor( scores.overall ) } }>
					{ scores.overall }/100
				</div>
			</div>

			{ /* Score rings grid */ }
			<div className={ scoreGrid }>
				<div className={ scoreItem }>
					<ScoreRing score={ scores.accessibility.score } size={ 44 } />
					<span className={ scoreLabel }>A11y</span>
				</div>
				<div className={ scoreItem }>
					<ScoreRing score={ scores.seo.score } size={ 44 } />
					<span className={ scoreLabel }>SEO</span>
				</div>
				<div className={ scoreItem }>
					<ScoreRing score={ scores.structure.score } size={ 44 } />
					<span className={ scoreLabel }>Structure</span>
				</div>
				<div className={ scoreItem }>
					<ScoreRing score={ scores.visual.score } size={ 44 } />
					<span className={ scoreLabel }>Visual</span>
				</div>
			</div>

			{ /* Details toggle */ }
			{ allIssues.length > 0 && (
				<>
					<button
						type="button"
						onClick={ () => setShowDetails( ! showDetails ) }
						className={ detailsToggle }
					>
						{ allIssues.length } suggestion{ allIssues.length !== 1 ? 's' : '' }
						{ showDetails ? <ChevronUp size={ 10 } /> : <ChevronDown size={ 10 } /> }
					</button>

					{ showDetails && (
						<div className={ detailsList }>
							{ allIssues.map( ( issue, i ) => (
								<div key={ i } className={ detailItem }>
									<span style={ { color: '#f59e0b', flexShrink: 0 } }>&#9679;</span>
									{ issue }
								</div>
							) ) }
						</div>
					) }
				</>
			) }

			{ /* Improve button */ }
			<button
				type="button"
				onClick={ handleImprove }
				className={ improveBtnStyle }
			>
				<Send size={ 12 } />
				{ scores.overall >= 80 ? 'Add final polish' : 'Fix issues with JARVIS' }
			</button>
		</div>
	);
}

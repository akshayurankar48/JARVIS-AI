/**
 * Prompt Gallery — Curated prompt template browser.
 *
 * Displays a filterable gallery of pre-built prompts organized by category.
 * Can be used in the Dashboard and editor WelcomeScreen.
 *
 * @package
 * @since 1.2.0
 */

import { useState, useMemo } from '@wordpress/element';
import {
	Rocket,
	Cpu,
	ShoppingBag,
	Image,
	FileText,
	UtensilsCrossed,
	Briefcase,
	Zap,
	Sparkles,
	ArrowRight,
	LayoutGrid,
	ChevronDown,
	ChevronUp,
} from 'lucide-react';
import templateData from '../data/prompt-templates.json';

/* ── Icon Map ──────────────────────────────────────────────────── */

const ICON_MAP = {
	Rocket,
	Cpu,
	ShoppingBag,
	Image,
	FileText,
	UtensilsCrossed,
	Briefcase,
	Zap,
};

/* ── Category Colors ───────────────────────────────────────────── */

const CATEGORY_STYLES = {
	'landing-pages': { bg: 'bg-violet-50', text: 'text-violet-600', border: 'border-l-violet-500' },
	saas: { bg: 'bg-indigo-50', text: 'text-indigo-600', border: 'border-l-indigo-500' },
	ecommerce: { bg: 'bg-amber-50', text: 'text-amber-600', border: 'border-l-amber-500' },
	portfolio: { bg: 'bg-rose-50', text: 'text-rose-600', border: 'border-l-rose-500' },
	blog: { bg: 'bg-emerald-50', text: 'text-emerald-600', border: 'border-l-emerald-500' },
	restaurant: { bg: 'bg-orange-50', text: 'text-orange-600', border: 'border-l-orange-500' },
	agency: { bg: 'bg-cyan-50', text: 'text-cyan-600', border: 'border-l-cyan-500' },
	startup: { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-l-blue-500' },
};

/* ── Template Card ─────────────────────────────────────────────── */

function TemplateCard( { template, onSelect } ) {
	const style = CATEGORY_STYLES[ template.category ] || CATEGORY_STYLES.saas;

	return (
		<button
			type="button"
			onClick={ () => onSelect( template.prompt ) }
			className={ `group flex flex-col gap-2 p-4 rounded-xl border border-solid border-border-subtle border-l-4 ${ style.border } bg-background-primary hover:shadow-md hover:border-border-interactive transition-all duration-200 cursor-pointer text-left w-full` }
		>
			<div className="flex items-center justify-between">
				<h3 className="text-sm font-semibold text-text-primary group-hover:text-brand-800 transition-colors duration-150">
					{ template.title }
				</h3>
				<ArrowRight className="size-3.5 text-text-tertiary opacity-0 group-hover:opacity-100 transition-opacity duration-200 shrink-0" />
			</div>
			<p className="text-xs text-text-secondary leading-relaxed line-clamp-2">
				{ template.description }
			</p>
			<div className="flex items-center gap-1.5 mt-auto">
				{ template.tags.map( ( tag ) => (
					<span
						key={ tag }
						className={ `inline-flex px-1.5 py-0.5 rounded text-[9px] font-medium ${ style.bg } ${ style.text }` }
					>
						{ tag }
					</span>
				) ) }
			</div>
		</button>
	);
}

/* ── Main Component ────────────────────────────────────────────── */

/**
 * PromptGallery component.
 *
 * @param {Object}   props          Component props.
 * @param {Function} props.onSelect Callback when a template prompt is selected.
 * @param {boolean}  props.compact  If true, shows a condensed version (for editor sidebar).
 * @param {number}   props.limit    Max number of templates to show before "Show more".
 */
export default function PromptGallery( { onSelect, compact = false, limit = 0 } ) {
	const [ activeCategory, setActiveCategory ] = useState( 'all' );
	const [ expanded, setExpanded ] = useState( false );

	const { categories, templates } = templateData;

	const filtered = useMemo( () => {
		if ( activeCategory === 'all' ) {
			return templates;
		}
		return templates.filter( ( t ) => t.category === activeCategory );
	}, [ activeCategory, templates ] );

	const displayLimit = limit > 0 ? limit : ( compact ? 6 : 0 );
	const hasMore = displayLimit > 0 && filtered.length > displayLimit;
	const displayed = ( displayLimit > 0 && ! expanded )
		? filtered.slice( 0, displayLimit )
		: filtered;

	return (
		<div className={ compact ? '' : 'bg-background-primary border border-solid border-border-subtle rounded-2xl shadow-sm p-6' }>
			{ ! compact && (
				<div className="flex items-center gap-2 mb-4">
					<LayoutGrid className="size-4 text-text-secondary" />
					<h2 className="text-base font-bold text-text-primary">
						Prompt Templates
					</h2>
					<span className="text-xs text-text-tertiary">
						{ templates.length } templates
					</span>
				</div>
			) }

			{ /* Category filter pills */ }
			<div className="flex items-center gap-1.5 mb-4 overflow-x-auto pb-1 scrollbar-none">
				<button
					type="button"
					onClick={ () => setActiveCategory( 'all' ) }
					className={ `flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 border border-solid shrink-0 cursor-pointer ${
						activeCategory === 'all'
							? 'bg-indigo-50 text-indigo-600 border-indigo-200'
							: 'bg-transparent text-text-tertiary border-border-subtle hover:text-text-secondary hover:border-border-interactive'
					}` }
				>
					<Sparkles className="size-3" />
					All
				</button>
				{ categories.map( ( cat ) => {
					const CatIcon = ICON_MAP[ cat.icon ] || Zap;
					const isActive = activeCategory === cat.id;
					const style = CATEGORY_STYLES[ cat.id ] || CATEGORY_STYLES.saas;
					return (
						<button
							key={ cat.id }
							type="button"
							onClick={ () => setActiveCategory( cat.id ) }
							className={ `flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-150 border border-solid shrink-0 cursor-pointer ${
								isActive
									? `${ style.bg } ${ style.text } border-current/20`
									: 'bg-transparent text-text-tertiary border-border-subtle hover:text-text-secondary hover:border-border-interactive'
							}` }
						>
							<CatIcon className="size-3" />
							{ cat.label }
						</button>
					);
				} ) }
			</div>

			{ /* Template grid */ }
			<div className={ `grid gap-3 ${ compact ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' }` }>
				{ displayed.map( ( template ) => (
					<TemplateCard
						key={ template.id }
						template={ template }
						onSelect={ onSelect }
					/>
				) ) }
			</div>

			{ /* Show more / less */ }
			{ hasMore && (
				<div className="flex justify-center mt-4">
					<button
						type="button"
						onClick={ () => setExpanded( ! expanded ) }
						className="flex items-center gap-1.5 px-4 py-2 text-xs font-medium text-text-tertiary hover:text-text-primary bg-transparent border border-solid border-border-subtle rounded-lg cursor-pointer hover:border-border-interactive transition-all duration-150"
					>
						{ expanded ? (
							<>
								<ChevronUp className="size-3" />
								Show less
							</>
						) : (
							<>
								<ChevronDown className="size-3" />
								Show all { filtered.length } templates
							</>
						) }
					</button>
				</div>
			) }
		</div>
	);
}

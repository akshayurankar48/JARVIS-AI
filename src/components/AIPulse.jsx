/**
 * AI Pulse — AI News & Learning Hub.
 *
 * Fetches curated AI news from RSS feeds via the REST API
 * and displays them in a card grid with source filters.
 *
 * @package
 * @since 1.2.0
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Rss,
	ExternalLink,
	RefreshCw,
	Newspaper,
	BookOpen,
	Sparkles,
	Play,
} from 'lucide-react';

/* ── Source icon colors ─────────────────────────────────────────── */

const SOURCE_STYLES = {
	openai: { bg: 'bg-emerald-50', text: 'text-emerald-600', label: 'OpenAI' },
	anthropic: { bg: 'bg-orange-50', text: 'text-orange-600', label: 'Anthropic' },
	verge: { bg: 'bg-violet-50', text: 'text-violet-600', label: 'The Verge' },
	techcrunch: { bg: 'bg-green-50', text: 'text-green-600', label: 'TechCrunch' },
	wordpress: { bg: 'bg-blue-50', text: 'text-blue-600', label: 'WordPress' },
	youtube: { bg: 'bg-red-50', text: 'text-red-600', label: 'YouTube' },
};

const TABS = [
	{ key: 'all', label: 'All', icon: Sparkles },
	{ key: 'blog', label: 'Blogs', icon: BookOpen },
	{ key: 'video', label: 'Videos', icon: Play },
	{ key: 'news', label: 'News', icon: Newspaper },
];

/* ── Relative time helper ──────────────────────────────────────── */

function timeAgo( dateStr ) {
	if ( ! dateStr ) {
		return '';
	}
	const now = Date.now();
	const then = new Date( dateStr ).getTime();
	const seconds = Math.floor( ( now - then ) / 1000 );

	if ( seconds < 60 ) {
		return 'just now';
	}
	const minutes = Math.floor( seconds / 60 );
	if ( minutes < 60 ) {
		return `${ minutes }m ago`;
	}
	const hours = Math.floor( minutes / 60 );
	if ( hours < 24 ) {
		return `${ hours }h ago`;
	}
	const days = Math.floor( hours / 24 );
	if ( days < 7 ) {
		return `${ days }d ago`;
	}
	return new Date( dateStr ).toLocaleDateString( undefined, { month: 'short', day: 'numeric' } );
}

/* ── Feed Item Card ────────────────────────────────────────────── */

function FeedCard( { item } ) {
	const style = SOURCE_STYLES[ item.icon ] || SOURCE_STYLES.openai;
	const isVideo = item.type === 'video';

	return (
		<a
			href={ item.link }
			target="_blank"
			rel="noopener noreferrer"
			className="group flex flex-col gap-2 p-4 rounded-xl border border-solid border-border-subtle bg-background-primary hover:shadow-md hover:border-border-interactive transition-all duration-200 no-underline"
		>
			{ /* Video thumbnail */ }
			{ isVideo && item.thumbnail && (
				<div className="relative rounded-lg overflow-hidden -mx-1 -mt-1 mb-0.5">
					<img
						src={ item.thumbnail }
						alt=""
						className="w-full h-auto aspect-video object-cover"
						loading="lazy"
					/>
					<div className="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/30 transition-colors duration-200">
						<div className="flex items-center justify-center size-9 rounded-full bg-red-600 shadow-lg">
							<Play className="size-4 text-white fill-white ml-0.5" />
						</div>
					</div>
				</div>
			) }
			<div className="flex items-center justify-between gap-2">
				<span className={ `inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${ style.bg } ${ style.text }` }>
					{ style.label }
				</span>
				<span className="text-[10px] text-text-tertiary shrink-0">
					{ timeAgo( item.published ) }
				</span>
			</div>
			<h3 className="text-sm font-semibold text-text-primary leading-snug line-clamp-2 group-hover:text-brand-800 transition-colors duration-150">
				{ item.title }
			</h3>
			{ item.summary && ! isVideo && (
				<p className="text-xs text-text-secondary leading-relaxed line-clamp-2">
					{ item.summary }
				</p>
			) }
			<div className="flex items-center gap-1 text-[10px] text-text-tertiary opacity-0 group-hover:opacity-100 transition-opacity duration-200 mt-auto">
				<ExternalLink className="size-3" />
				<span>{ isVideo ? 'Watch video' : 'Read article' }</span>
			</div>
		</a>
	);
}

/* ── Main Component ────────────────────────────────────────────── */

export default function AIPulse() {
	const [ feed, setFeed ] = useState( null );
	const [ activeTab, setActiveTab ] = useState( 'all' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	const fetchFeed = useCallback( ( refresh = false ) => {
		if ( refresh ) {
			setIsRefreshing( true );
		}
		apiFetch( { path: `/jarvis-ai/v1/ai-pulse${ refresh ? '?refresh=true' : '' }` } )
			.then( ( data ) => {
				setFeed( data );
				setIsLoading( false );
				setIsRefreshing( false );
			} )
			.catch( () => {
				setIsLoading( false );
				setIsRefreshing( false );
			} );
	}, [] );

	useEffect( () => {
		fetchFeed();
	}, [ fetchFeed ] );

	const items = feed?.items || [];
	const filtered = activeTab === 'all'
		? items
		: items.filter( ( item ) => item.type === activeTab );

	return (
		<div className="bg-background-primary border border-solid border-border-subtle rounded-2xl shadow-sm p-6">
			<div className="flex items-center justify-between mb-4">
				<div className="flex items-center gap-2">
					<Rss className="size-4 text-text-secondary" />
					<h2 className="text-base font-bold text-text-primary">
						AI Pulse
					</h2>
					<span className="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-gradient-to-r from-indigo-500 to-violet-500 text-white uppercase tracking-wider">
						Live
					</span>
				</div>
				<button
					type="button"
					onClick={ () => fetchFeed( true ) }
					disabled={ isRefreshing }
					className="flex items-center gap-1 text-xs font-medium text-text-tertiary hover:text-brand-800 transition-colors duration-200 bg-transparent border-none cursor-pointer disabled:opacity-40"
				>
					<RefreshCw className={ `size-3 ${ isRefreshing ? 'animate-spin' : '' }` } />
					Refresh
				</button>
			</div>

			{ /* Tab filters */ }
			<div className="flex items-center gap-1 mb-4 p-0.5 bg-background-secondary rounded-lg w-fit">
				{ TABS.map( ( tab ) => (
					<button
						key={ tab.key }
						type="button"
						onClick={ () => setActiveTab( tab.key ) }
						className={ `flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-150 border-none cursor-pointer ${
							activeTab === tab.key
								? 'bg-background-primary text-text-primary shadow-sm'
								: 'bg-transparent text-text-tertiary hover:text-text-secondary'
						}` }
					>
						<tab.icon className="size-3" />
						{ tab.label }
					</button>
				) ) }
			</div>

			{ /* Content */ }
			{ isLoading ? (
				<div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
					{ [ 1, 2, 3, 4 ].map( ( i ) => (
						<div key={ i } className="h-28 rounded-xl bg-background-secondary animate-pulse" />
					) ) }
				</div>
			) : filtered.length > 0 ? (
				<div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[420px] overflow-y-auto pr-1">
					{ filtered.map( ( item, i ) => (
						<FeedCard key={ i } item={ item } />
					) ) }
				</div>
			) : (
				<div className="flex flex-col items-center justify-center py-8 text-center">
					<div className="flex items-center justify-center size-12 rounded-full bg-background-secondary mb-3">
						<Rss className="size-5 text-text-tertiary" />
					</div>
					<p className="text-sm text-text-secondary font-medium mb-1">No articles found</p>
					<p className="text-xs text-text-tertiary">Try refreshing or check back later</p>
				</div>
			) }
		</div>
	);
}

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Badge, Button } from '@bsf/force-ui';
import { Clock, RefreshCw, Loader2, MessageSquare, ChevronLeft, ChevronRight } from 'lucide-react';
import PageLayout from '../components/PageLayout';

function ConversationRow( { conversation } ) {
	const date = new Date( conversation.created_at );
	const formatted = date.toLocaleDateString( undefined, {
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	} );

	return (
		<tr className="border-b border-solid border-border-subtle last:border-b-0 hover:bg-background-secondary transition-colors">
			<td className="py-3 px-4">
				<span className="text-sm font-medium text-text-primary">
					{ conversation.title || 'Untitled conversation' }
				</span>
			</td>
			<td className="py-3 px-4">
				<Badge
					label={ conversation.status }
					variant={ conversation.status === 'active' ? 'green' : 'neutral' }
					size="xs"
				/>
			</td>
			<td className="py-3 px-4">
				<span className="text-xs text-text-secondary">
					{ conversation.model || '-' }
				</span>
			</td>
			<td className="py-3 px-4">
				<span className="text-xs text-text-secondary">
					{ conversation.tokens_used.toLocaleString() }
				</span>
			</td>
			<td className="py-3 px-4">
				<span className="text-xs text-text-tertiary">{ formatted }</span>
			</td>
		</tr>
	);
}

export default function History() {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ page, setPage ] = useState( 1 );

	const fetchHistory = useCallback( async ( p ) => {
		try {
			setLoading( true );
			const result = await apiFetch( {
				path: `/wp-agent/v1/history?page=${ p }&per_page=20`,
			} );
			setData( result );
		} catch {
			setData( null );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchHistory( page );
	}, [ fetchHistory, page ] );

	const conversations = data?.conversations || [];
	const totalPages = data?.total_pages || 1;

	return (
		<PageLayout>
			<div className="flex items-center justify-between mb-6">
				<div className="flex items-center gap-3">
					<h1 className="text-xl font-semibold text-text-primary">
						History
					</h1>
					{ data && (
						<Badge
							label={ `${ data.total } conversation${ data.total !== 1 ? 's' : '' }` }
							variant="neutral"
							size="xs"
						/>
					) }
				</div>
				<Button
					variant="ghost"
					size="xs"
					icon={ <RefreshCw size={ 14 } /> }
					onClick={ () => fetchHistory( page ) }
					disabled={ loading }
				>
					Refresh
				</Button>
			</div>

			{ loading ? (
				<div className="flex items-center justify-center py-16">
					<Loader2 size={ 24 } className="animate-spin text-icon-secondary" />
				</div>
			) : conversations.length === 0 ? (
				<div className="flex flex-col items-center justify-center rounded-xl border border-solid border-border-subtle bg-background-primary p-12 shadow-sm text-center">
					<div className="flex items-center justify-center w-16 h-16 rounded-full bg-background-secondary mb-4">
						<Clock size={ 28 } className="text-icon-secondary" />
					</div>
					<h2 className="text-lg font-semibold text-text-primary mb-2">
						No conversations yet
					</h2>
					<p className="text-sm text-text-secondary max-w-md">
						Open the editor and start chatting with JARVIS.
						Your conversation history will appear here.
					</p>
				</div>
			) : (
				<>
					<div className="rounded-xl border border-solid border-border-subtle bg-background-primary shadow-sm overflow-x-auto">
						<table className="w-full text-left border-collapse">
							<thead>
								<tr className="border-b border-solid border-border-subtle bg-background-secondary">
									<th className="py-2.5 px-4 text-xs font-medium text-text-tertiary uppercase tracking-wide">Title</th>
									<th className="py-2.5 px-4 text-xs font-medium text-text-tertiary uppercase tracking-wide">Status</th>
									<th className="py-2.5 px-4 text-xs font-medium text-text-tertiary uppercase tracking-wide">Model</th>
									<th className="py-2.5 px-4 text-xs font-medium text-text-tertiary uppercase tracking-wide">Tokens</th>
									<th className="py-2.5 px-4 text-xs font-medium text-text-tertiary uppercase tracking-wide">Date</th>
								</tr>
							</thead>
							<tbody>
								{ conversations.map( ( conv ) => (
									<ConversationRow key={ conv.id } conversation={ conv } />
								) ) }
							</tbody>
						</table>
					</div>

					{ totalPages > 1 && (
						<div className="flex items-center justify-center gap-2 mt-4">
							<Button
								variant="ghost"
								size="xs"
								icon={ <ChevronLeft size={ 14 } /> }
								onClick={ () => setPage( ( p ) => Math.max( 1, p - 1 ) ) }
								disabled={ page <= 1 }
							/>
							<span className="text-xs text-text-secondary">
								Page { page } of { totalPages }
							</span>
							<Button
								variant="ghost"
								size="xs"
								icon={ <ChevronRight size={ 14 } /> }
								onClick={ () => setPage( ( p ) => Math.min( totalPages, p + 1 ) ) }
								disabled={ page >= totalPages }
							/>
						</div>
					) }
				</>
			) }
		</PageLayout>
	);
}

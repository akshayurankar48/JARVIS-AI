import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import Dashboard from './pages/Dashboard';
import Settings from './pages/Settings';
import History from './pages/History';
import Schedules from './pages/Schedules';
import Capabilities from './pages/Capabilities';
import Help from './pages/Help';
import AdminChatDrawer from './drawer/AdminChatDrawer';
import './store';
import './style.css';

const pages = [
	{ id: 'jarvis-ai-dashboard', Component: Dashboard },
	{ id: 'jarvis-ai-settings', Component: Settings },
	{ id: 'jarvis-ai-history', Component: History },
	{ id: 'jarvis-ai-schedules', Component: Schedules },
	{ id: 'jarvis-ai-capabilities', Component: Capabilities },
	{ id: 'jarvis-ai-help', Component: Help },
];

domReady( () => {
	for ( const { id, Component } of pages ) {
		const container = document.getElementById( id );
		if ( container ) {
			createRoot( container ).render( <Component /> );
			break;
		}
	}

	// Mount the JARVIS chat drawer on all JARVIS AI admin pages.
	// Uses Emotion-based drawer (not Tailwind) since it renders via body portal.
	const drawerRoot = document.createElement( 'div' );
	drawerRoot.id = 'jarvis-ai-app-drawer';
	document.body.appendChild( drawerRoot );
	createRoot( drawerRoot ).render( <AdminChatDrawer /> );
} );

import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import App from './App';
import './style.css';

domReady( () => {
	const container = document.getElementById( 'wp-agent-settings' );
	if ( container ) {
		createRoot( container ).render( <App /> );
	}
} );

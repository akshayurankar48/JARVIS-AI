const withTW = require( '@bsf/force-ui/withTW' );

module.exports = withTW( {
	content: [ './src/**/*.{js,jsx}' ],
	corePlugins: {
		preflight: false,
	},
	important:
		':is(#jarvis-ai-dashboard, #jarvis-ai-settings, #jarvis-ai-history, #jarvis-ai-schedules, #jarvis-ai-capabilities, #jarvis-ai-help, #jarvis-ai-sidebar, [data-floating-ui-portal])',
} );

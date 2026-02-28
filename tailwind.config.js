const withTW = require( '@bsf/force-ui/withTW' );

module.exports = withTW( {
	content: [ './src/**/*.{js,jsx}' ],
	corePlugins: {
		preflight: false,
	},
	important: ':is(#wp-agent-settings, [data-floating-ui-portal])',
} );

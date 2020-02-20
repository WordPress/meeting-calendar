const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		calendar: path.resolve( __dirname, 'src/components/calendar/calendar.js' ),
	},
};

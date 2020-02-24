const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		calendar: path.resolve( __dirname, 'src/frontend/index.js' ),
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.css$/,
				use: [ MiniCssExtractPlugin.loader, 'css-loader' ],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins,
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} ),
	],
};

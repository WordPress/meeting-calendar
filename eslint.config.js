/**
 * ESLint configuration, extending the `@wordpress/scripts` default.
 */

/**
 * External dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		settings: {
			// These imports are externalized to scripts supplied by WordPress.
			'import/core-modules': [
				'@wordpress/a11y',
				'@wordpress/blocks',
				'@wordpress/components',
				'@wordpress/date',
				'@wordpress/element',
				'@wordpress/i18n',
				'@wordpress/primitives',
				'@wordpress/url',
				'lodash',
			],
		},
		rules: {
			// Every function gets a docblock, including arrow functions and methods.
			'jsdoc/require-jsdoc': [
				'error',
				{
					publicOnly: false,
					require: {
						ArrowFunctionExpression: true,
						FunctionDeclaration: true,
						FunctionExpression: true,
					},
				},
			],
		},
	},
];

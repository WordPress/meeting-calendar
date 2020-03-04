/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element';
/**
 * Internal dependencies
 */
import useWindowSize from '../store/hooks/use-window-size';

const STYLE_PROPS = [ 'flexDirection', 'display', 'margin', 'padding' ];
const CONTROL_PROPS = [ 'as' ];

const getProp = ( isSmall, prop ) => {
	if ( prop ) {
		let propIdx = 0;

		// If we have one, we'll use it
		if ( prop.length > 1 ) {
			propIdx = isSmall ? 0 : 1;
		}

		return prop[ propIdx ];
	}
	return undefined;
};

const getProps = ( isSmall, props ) => {
	const obj = {};

	Object.keys( props ).forEach( ( key ) => {
		// We don't support that prop
		if ( STYLE_PROPS.indexOf( key ) < 0 ) {
			return;
		}

		obj[ key ] = getProp( isSmall, props[ key ] );
	} );

	return obj;
};

const cleanProps = ( props ) => {
	const obj = {};

	const allProps = STYLE_PROPS.concat( CONTROL_PROPS );
	Object.keys( props ).forEach( ( key ) => {
		if ( allProps.indexOf( key ) >= 0 ) {
			return;
		}

		obj[ key ] = props[ key ];
	} );

	return obj;
};

const Container = ( props ) => {
	const { isSmall } = useWindowSize();
	const defaultProps = getProps( isSmall, props );

	const Tag = props.as.length ? props.as : Fragment;
	return (
		<Tag
			className={ props.className }
			style={ defaultProps }
			{ ...cleanProps( props ) }
		>
			{ props.children }
		</Tag>
	);
};

export default Container;

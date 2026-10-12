/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';

const breakpoints = {
	small: 600,
};

const isClient = typeof window === 'object';

/**
 * Get the current viewport size category.
 *
 * @return {Object} Whether the viewport is small.
 */
function getSize() {
	return {
		isSmall: isClient ? window.innerWidth < breakpoints.small : undefined,
	};
}

/**
 * Track the viewport size category, updating on resize.
 *
 * @return {Object} Whether the viewport is small.
 */
const useWindowSize = () => {
	const [ windowSize, setWindowSize ] = useState( getSize );

	useEffect( () => {
		if ( ! isClient ) {
			return false;
		}

		/**
		 * Update the size after a resize.
		 */
		const handleResize = () => {
			setWindowSize( getSize() );
		};

		window.addEventListener( 'resize', handleResize );
		return () => window.removeEventListener( 'resize', handleResize );
	}, [] );

	return windowSize;
};

export default useWindowSize;

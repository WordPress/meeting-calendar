/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';

const breakpoints = {
	small: 600,
};

const isClient = typeof window === 'object';

function getSize() {
	return {
		isSmall: isClient ? window.innerWidth < breakpoints.small : undefined,
	};
}

const useWindowSize = () => {
	const [ windowSize, setWindowSize ] = useState( getSize );

	useEffect( () => {
		if ( ! isClient ) {
			return false;
		}

		const handleResize = () => {
			setWindowSize( getSize() );
		};

		window.addEventListener( 'resize', handleResize );
		return () => window.removeEventListener( 'resize', handleResize );
	}, [] );

	return windowSize;
};

export default useWindowSize;

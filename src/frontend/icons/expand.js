/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

export default function () {
	return (
		<SVG
			xmlns="http://www.w3.org/2000/svg"
			height="24"
			viewBox="0 0 24 24"
			width="24"
		>
			<Path d="M0 0h24v24H0z" fill="none" />
			<Path d="M12 5.83L15.17 9l1.41-1.41L12 3 7.41 7.59 8.83 9 12 5.83zm0 12.34L8.83 15l-1.41 1.41L12 21l4.59-4.59L15.17 15 12 18.17z" />
		</SVG>
	);
}

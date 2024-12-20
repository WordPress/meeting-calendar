/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

export default function () {
	return (
		<SVG
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/SVG"
		>
			<Path d="M7 10H9V12H7V10Z" />
			<Path d="M9 14H7V16H9V14Z" />
			<Path d="M11 10H13V12H11V10Z" />
			<Path d="M17 10H15V12H17V10Z" />
			<Path d="M11 14H13V16H11V14Z" />
			<Path d="M17 14H15V16H17V14Z" />
			<Path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M3 5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5ZM19.5 7H4.5V19C4.5 19.2761 4.72386 19.5 5 19.5H19C19.2761 19.5 19.5 19.2761 19.5 19V7Z"
			/>
		</SVG>
	);
}

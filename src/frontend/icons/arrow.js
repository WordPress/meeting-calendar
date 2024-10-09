/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

export default function({ fill = '#3858E9' }) {
	return (
		<SVG width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<Path fillRule="evenodd" clipRule="evenodd" d="M9.46978 6.46967L10.5304 7.53033L6.81077 11.25L20.0001 11.25V12.75L6.81077 12.75L10.5304 16.4697L9.46978 17.5303L3.93945 12L9.46978 6.46967Z" fill={ fill }/>
		</SVG>
	);
}

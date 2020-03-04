/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useEvents } from '../store/event-context';
import Container from '../container';

const Filter = () => {
	const { teams, team, setTeam } = useEvents();

	return (
		<Container
			as="div"
			className="wporg-meeting-calendar__filter"
			flexDirection={ [ 'column', 'row' ] }
		>
			<SelectControl
				label={ __( 'Filter by team', 'wporg' ) }
				value={ team }
				options={ [
					{ label: __( 'All teams', 'wporg' ), value: '' },
					...teams,
				] }
				onChange={ ( value ) => {
					setTeam( value );
				} }
			/>
			{ '' !== team && (
				<>
					<p>
						Showing meetings for{ ' ' }
						<span style={ { textTransform: 'capitalize' } }>
							{ team } team.
						</span>
					</p>
					<Button
						icon="no-alt"
						isLink
						isDestructive
						onClick={ () => void setTeam( '' ) }
					>
						{ __( 'Remove team filter', 'wporg' ) }
					</Button>
				</>
			) }
		</Container>
	);
};

export default Filter;

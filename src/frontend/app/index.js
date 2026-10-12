/**
 * Internal dependencies
 */
import { ViewProvider } from '../store/view-context';
import { EventsProvider } from '../store/event-context';
import useWindowSize from '../store/hooks/use-window-size';
import Calendar from '../calendar';

/**
 * Root component, providing the events and view state to the calendar.
 *
 * @param {Object}   props        Component props.
 * @param {Object[]} props.events Meeting occurrences.
 * @return {Element} App element.
 */
function App( { events } ) {
	const { isSmall } = useWindowSize();

	return (
		<EventsProvider value={ events }>
			<ViewProvider isSmallViewport={ isSmall }>
				<Calendar />
			</ViewProvider>
		</EventsProvider>
	);
}

export default App;

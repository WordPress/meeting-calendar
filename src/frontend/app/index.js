/**
 * Internal dependencies
 */
import { ViewProvider } from '../store/view-context';
import { EventsProvider } from '../store/event-context';
import { EventDataProvider } from '../store/data-context';
import useWindowSize from '../store/hooks/use-window-size';
import Calendar from '../calendar';

function App( { events } ) {
	const { isSmall } = useWindowSize();

	return (
	<EventDataProvider>
		<EventsProvider>
			<ViewProvider isSmallViewport={ isSmall }>
				<Calendar />
			</ViewProvider>
		</EventsProvider>
	</EventDataProvider>
	);
}

export default App;

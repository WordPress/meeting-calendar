/**
 * Internal dependencies
 */
import { ViewProvider } from '../store/view-context';
import { EventsProvider } from '../store/event-context';
import Calendar from '../calendar';

function App( { events } ) {
	return (
		<EventsProvider value={ events }>
			<ViewProvider>
				<Calendar />
			</ViewProvider>
		</EventsProvider>
	);
}

export default App;

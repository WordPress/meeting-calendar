/**
 * Internal dependencies
 */
import { ViewProvider } from './view-context';
import { EventsProvider } from '../app/event-context';
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

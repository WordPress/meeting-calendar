/**
 * Internal dependencies
 */
import { ViewProvider } from '../store/view-context';
import { EventsProvider } from '../store/event-context';
import useWindowSize from '../store/hooks/use-window-size';
import Calendar from '../calendar';

function App( { events } ) {
	const { isSmall } = useWindowSize();

	return (
		<EventsProvider value={ events }>
			<ViewProvider value={ { isMobileOnLoad: isSmall } }>
				<Calendar />
			</ViewProvider>
		</EventsProvider>
	);
}

export default App;

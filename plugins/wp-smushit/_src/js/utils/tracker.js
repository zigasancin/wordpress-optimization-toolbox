import Fetcher from './fetcher';

class Tracker {
	/* @private */
	#doingEvents = new Set();
	#allowToTrack;

	track( event, properties = {} ) {
		if ( ! this.allowToTrack() || this.inProgressEvent( event ) ) {
			return;
		}

		this.setInProgressEvent( event );

		return Fetcher.common.track( event, properties ).then( ( res ) => {
			setTimeout( () => {
				this.restoreInProgressEvent( event );
			}, 1000 );

			return res;
		} );
	}

	inProgressEvent( event ) {
		return this.#doingEvents.has( event );
	}

	setInProgressEvent( event ) {
		this.#doingEvents.add( event );
	}

	restoreInProgressEvent( event ) {
		this.#doingEvents.delete( event );
	}

	allowToTrack() {
		return this.#allowToTrack || !! ( window.wp_smush_mixpanel?.opt_in );
	}

	setAllowToTrack( allowToTrack ) {
		this.#allowToTrack = allowToTrack;

		return this;
	}
}

const tracker = new Tracker();

export default tracker;

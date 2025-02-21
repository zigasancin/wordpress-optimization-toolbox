// Deactivation survey.
import Fetcher from '../utils/fetcher';
import tracker from '../utils/tracker';

export default class DeactivationSurvey {
	constructor() {
		this.reason = 'not_set';
		this.requestedAssistance = 'na';
		this.modalAction = 'close';
		this.modalId = 'wp-smush-deactivation-survey-modal';
		this.modal = document.getElementById( this.modalId );
	}

	init() {
		if ( ! this.modal ) {
			return;
		}

		this.handleSurveyModal();
	}

	handleSurveyModal() {
		this.deactivatePluginLink = document.querySelector( 'a[id^="deactivate-smush"]' ) || document.querySelector( 'a[id^="deactivate-wp-smushit"]' );
		if ( ! this.deactivatePluginLink ) {
			return;
		}

		this.deactivatePluginLink.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			// Show modal.
			this.showModal();

			// Handle survey form.
			this.handleSurveyForm();
		} );
	}

	handleSurveyForm() {
		this.registerRequestAssistanceLinkClickEvent();
		this.handleRadioChange();
		this.handleSkipDeactivation();
		this.handleSubmitForm();
		this.handleTrackDeactivate();
	}

	registerRequestAssistanceLinkClickEvent() {
		const requestAssistanceLink = this.modal.querySelector( '#smush-request-assistance-link' );
		if ( requestAssistanceLink ) {
			this.requestedAssistance = 'no';
			requestAssistanceLink.addEventListener( 'click', () => {
				this.requestedAssistance = 'yes';
			} );
		}
	}

	handleRadioChange() {
		const that = this;
		this.userMessageField = document.getElementById( 'smush-deactivation-user-message-field' );
		if ( ! this.userMessageField ) {
			return;
		}

		this.modal.querySelectorAll( 'input[type="radio"]' ).forEach( ( inputRadio ) => {
			inputRadio.addEventListener( 'change', function() {
				that.reason = this.value;
				that.toggleUserMessageField( this.parentElement );
			} );
		} );
	}

	handleSkipDeactivation() {
		const skipButton = this.modal.querySelector( '.smush-skip-deactivate-button' );
		if ( ! skipButton ) {
			return;
		}

		skipButton.addEventListener( 'click', ( e ) => {
			e.target.classList.add( 'sui-button-onload' );

			this.modalAction = 'skip';
			// Close modal and track on closed event.
			this.closeModal();

			// Deactivate the plugin when tracking is disabled; otherwise, handle it after tracking.
			// @see this.trackDeactivate().
			if ( ! tracker.allowToTrack() ) {
				this.redirectToDeactivateLink();
			}
		}, { once: true } );
	}

	handleSubmitForm() {
		const submitButton = this.modal.querySelector( '.smush-submit-deactivate-button' );
		if ( ! submitButton ) {
			return;
		}

		submitButton.addEventListener( 'click', ( e ) => {
			e.target.classList.add( 'sui-button-onload' );

			this.modalAction = 'submit';
			// Close modal and track on closed event.
			this.closeModal();

			// Plugin deactivation has been handled after tracking.
			// @see this.trackDeactivate().
		}, { once: true } );
	}

	toggleUserMessageField( labelField ) {
		if ( ! this.userMessageField ) {
			return;
		}

		// Remove current user message field.
		this.userMessageField.remove();

		const placeholder = labelField.dataset?.placeholder;
		if ( ! placeholder ) {
			return;
		}

		// Update placeholder.
		const textarea = this.userMessageField.querySelector( 'textarea' );
		textarea.placeholder = placeholder;

		// Append user message field.
		labelField.after( this.userMessageField );
		this.userMessageField.classList.remove( 'sui-hidden' );

		// Focus on textarea.
		textarea.focus();
	}

	getDeactivateLink() {
		return this.deactivatePluginLink.href;
	}

	showModal() {
		const focusAfterClosed = 'wpbody-content',
			focusWhenOpen = undefined,
			hasOverlayMask = true,
			isCloseOnEsc = false,
			isAnimated = true;

		window.SUI?.openModal(
			this.modalId,
			focusAfterClosed,
			focusWhenOpen,
			hasOverlayMask,
			isCloseOnEsc,
			isAnimated
		);
	}

	closeModal() {
		window.SUI?.closeModal( true );
	}

	handleTrackDeactivate() {
		this.modal.addEventListener( 'afterClose', () => this.trackDeactivate(), { once: true } );
	}

	trackDeactivate() {
		if ( ! this.shouldTrack() ) {
			return;
		}

		const event = 'Deactivation Survey';
		const textarea = this.userMessageField.querySelector( 'textarea' );
		const message = textarea.value;
		const properties = {
			Reason: this.reason,
			Message: message,
			'Modal Action': this.modalAction,
			'Requested Assistance': this.requestedAssistance,
			'Tracking Status': tracker.allowToTrack() ? 'opted_in' : 'opted_out',
		};

		Fetcher.common.request( {
			action: 'smush_track_deactivate',
			event,
			properties,
		} ).finally( () => {
			if ( this.shouldDeactivatePlugin() ) {
				this.redirectToDeactivateLink();
			}
		} );
	}

	shouldTrack() {
		return tracker.allowToTrack() || this.isSubmitAction();
	}

	isSubmitAction() {
		return 'submit' === this.modalAction;
	}

	shouldDeactivatePlugin() {
		const skipAndDeactivate = 'skip' === this.modalAction;

		return skipAndDeactivate || this.isSubmitAction();
	}

	redirectToDeactivateLink() {
		const deactivateLink = this.getDeactivateLink();
		window.location.href = deactivateLink;
	}
}

/* global WP_Smush */
/* global ajaxurl */

/**
 * WebP functionality.
 *
 * @since 3.8.0
 */
import Fetcher from '../utils/fetcher';
import NextGen from './next-gen';

class WebP extends NextGen {
	constructor() {
		super( 'webp' );

		this.recheckStatusButton = document.getElementById( 'smush-webp-recheck' );
		this.recheckStatusLink = document.getElementById( 'smush-webp-recheck-link' );
		this.showWizardButton = document.getElementById( 'smush-webp-toggle-wizard' );
		this.switchWebpMethod = document.getElementById( 'smush-switch-webp-method' );

		this.webpInit();
	}

	webpInit() {
		/**
		 * Handle "RE-CHECK STATUS' button click on WebP page.
		 */
		if ( this.recheckStatusButton ) {
			this.recheckStatusButton.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.recheckStatus();
			} );
		}

		/**
		 * Handle "RE-CHECK STATUS' link click on WebP page.
		 */
		if ( this.recheckStatusLink ) {
			this.recheckStatusLink.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.recheckStatus();
			} );
		}

		if ( this.showWizardButton ) {
			this.showWizardButton.addEventListener(
				'click',
				this.toggleWizard
			);
		}

		if ( this.switchWebpMethod ) {
			this.switchWebpMethod.addEventListener(
				'click',
				( e ) => {
					e.preventDefault();
					e.target.classList.add( 'wp-smush-link-in-progress' );
					this.switchMethod( this.switchWebpMethod.dataset.method );
				}
			);
		}
	}

	switchMethod( newMethod ) {
		Fetcher.webp.switchMethod( newMethod ).then( ( res ) => {
			if ( ! res?.success ) {
				WP_Smush.helpers.showNotice( res );
				return;
			}
			window.location.reload();
		} );
	}

	/**
	 * re-check server configuration for WebP.
	 */
	recheckStatus() {
		this.recheckStatusButton.classList.add( 'sui-button-onload' );

		const xhr = new XMLHttpRequest();
		xhr.open( 'POST', ajaxurl + '?action=smush_webp_get_status', true );
		xhr.setRequestHeader(
			'Content-type',
			'application/x-www-form-urlencoded'
		);
		xhr.onload = () => {
			this.recheckStatusButton.classList.remove( 'sui-button-onload' );
			let message = false;
			const res = JSON.parse( xhr.response );
			if ( 200 === xhr.status ) {
				const isConfigured = res.success ? '1' : '0';
				if (
					isConfigured !==
					this.recheckStatusButton.dataset.isConfigured
				) {
					// Reload the page when the configuration status changed.
					location.reload();
				}
			} else {
				message = window.wp_smush_msgs.generic_ajax_error;
			}

			if ( res && res.data ) {
				message = res.data;
			}

			if ( message ) {
				this.showNotice( message );
			}
		};
		xhr.send( '_ajax_nonce=' + window.wp_smush_msgs.webp_nonce );
	}

	toggleWizard( e ) {
		e.currentTarget.classList.add( 'sui-button-onload' );

		const xhr = new XMLHttpRequest();
		xhr.open(
			'GET',
			ajaxurl +
				'?action=smush_toggle_webp_wizard&_ajax_nonce=' +
				window.wp_smush_msgs.webp_nonce,
			true
		);
		xhr.onload = () => location.href = window.wp_smush_msgs.nextGenURL;
		xhr.send();
	}
}

( function() {
	'use strict';
	WP_Smush.WebP = new WebP();
}() );

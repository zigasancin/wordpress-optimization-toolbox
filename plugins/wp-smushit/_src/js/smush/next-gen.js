/* global ajaxurl */
/* global WP_Smush */

/**
 * NextGen class.
 */
export default class NextGen {
	constructor( nextGenFormat ) {
		this.nextGenFormat = nextGenFormat;
		this.nonceField = document.getElementsByName( 'wp_smush_options_nonce' );
		this.toggleModuleButton = document.getElementById( `smush-toggle-${ nextGenFormat }-button` );
		this.deleteAllButton = document.getElementById( `wp-smush-${ nextGenFormat }-delete-all` );

		this.registerGlobalEvents();
		this.registerEvents();
	}

	/**
	 * Register global events.
	 */
	registerGlobalEvents() {
		if ( NextGen.isGlobalEventListenerAdded ) {
			return;
		}
		NextGen.isGlobalEventListenerAdded = true;

		document.addEventListener( 'onSavedSmushSettings', this.onSavedNextGenSettingsHandler.bind( this ) );
		document.addEventListener( 'on-smush-next-gen-activated-notice', this.showNextGenActivatedModal.bind( this ) );
		document.addEventListener( 'on-smush-next-gen-conversion-changed-notice', this.showNextGenConversionChangedModal.bind( this ) );
	}

	registerEvents() {
		this.maybeShowDeleteAllSuccessNotice();

		/**
		 * Handles the "Deactivate" and "Get Started" buttons on the Next-Gen page.
		 */
		if ( this.toggleModuleButton ) {
			this.toggleModuleButton.addEventListener( 'click', ( e ) =>
				this.toggleModule( e )
			);
		}

		/**
		 * Handles the "Delete Next-Gen images" button.
		 */
		if ( this.deleteAllButton ) {
			this.deleteAllButton.addEventListener( 'click', ( e ) => this.deleteAll( e ) );
		}
	}

	/**
	 * Toggle Next-Gen module.
	 *
	 * @param {Event} e
	 */
	toggleModule( e ) {
		e.preventDefault();

		const button = e.currentTarget,
			doEnable = 'enable' === button.dataset.action;

		button.classList.add( 'sui-button-onload' );

		const xhr = new XMLHttpRequest();
		xhr.open( 'POST', ajaxurl + `?action=smush_${ this.nextGenFormat }_toggle`, true );
		xhr.setRequestHeader(
			'Content-type',
			'application/x-www-form-urlencoded'
		);

		xhr.onload = () => {
			const res = JSON.parse( xhr.response );

			if ( 200 === xhr.status ) {
				if ( 'undefined' !== typeof res.success && res.success ) {
					const scanPromise = this.runScan();
					scanPromise.onload = () => {
						this.redirectToNextGenPage();
					};
				} else if ( 'undefined' !== typeof res.data.message ) {
					this.showNotice( res.data.message );
					button.classList.remove( 'sui-button-onload' );
				}
			} else {
				let message = window.wp_smush_msgs.generic_ajax_error;
				if ( res && 'undefined' !== typeof res.data.message ) {
					message = res.data.message;
				}
				this.showNotice( message );
				button.classList.remove( 'sui-button-onload' );
			}
		};

		xhr.send(
			'param=' + doEnable + '&_ajax_nonce=' + this.nonceField[ 0 ].value
		);
	}

	deleteAll( e ) {
		const button = e.currentTarget;
		button.classList.add( 'sui-button-onload' );

		let message = false;
		const xhr = new XMLHttpRequest();
		xhr.open( 'POST', ajaxurl + `?action=smush_${ this.nextGenFormat }_delete_all`, true );
		xhr.setRequestHeader(
			'Content-type',
			'application/x-www-form-urlencoded'
		);

		xhr.onload = () => {
			const res = JSON.parse( xhr.response );
			if ( 200 === xhr.status ) {
				if ( 'undefined' !== typeof res.success && res.success ) {
					const scanPromise = this.runScan();
					scanPromise.onload = () => {
						location.search =
                            location.search + `&smush-notice=${ this.nextGenFormat }-deleted`;
					};
				} else {
					message = window.wp_smush_msgs.generic_ajax_error;
				}
			} else {
				message = window.wp_smush_msgs.generic_ajax_error;
			}

			if ( res && res.data && res.data.message ) {
				message = res.data.message;
			}

			if ( message ) {
				button.classList.remove( 'sui-button-onload' );

				const noticeMessage = `<p style="text-align: left;">${ message }</p>`;
				const noticeOptions = {
					type: 'error',
					icon: 'info',
					autoclose: {
						show: false,
					},
				};

				window.SUI.openNotice(
					'wp-smush-next-gen-delete-all-error-notice',
					noticeMessage,
					noticeOptions
				);
			}
		};

		xhr.send( '_ajax_nonce=' + this.nonceField[ 0 ].value );
	}

	/**
	 * Triggers the scanning of images for updating the images to re-smush.
	 *
	 * @since 3.8.0
	 */
	runScan() {
		const xhr = new XMLHttpRequest(),
			nonceField = document.getElementsByName(
				'wp_smush_options_nonce'
			);

		xhr.open( 'POST', ajaxurl + '?action=scan_for_resmush', true );
		xhr.setRequestHeader(
			'Content-type',
			'application/x-www-form-urlencoded'
		);

		xhr.send( '_ajax_nonce=' + nonceField[ 0 ].value );

		return xhr;
	}

	/**
	 * Show message (notice).
	 *
	 * @param {string} message
	 * @param {string} type
	 */
	showNotice( message, type ) {
		if ( 'undefined' === typeof message ) {
			return;
		}

		const noticeMessage = `<p>${ message }</p>`;
		const noticeOptions = {
			type: type || 'error',
			icon: 'info',
			dismiss: {
				show: true,
				label: window.wp_smush_msgs.noticeDismiss,
				tooltip: window.wp_smush_msgs.noticeDismissTooltip,
			},
			autoclose: {
				show: false,
			},
		};

		window.SUI.openNotice(
			'wp-smush-ajax-notice',
			noticeMessage,
			noticeOptions
		);
	}

	/**
	 * Show delete all webp success notice.
	 */
	maybeShowDeleteAllSuccessNotice() {
		const deletedAllNoticeElementID = `wp-smush-${ this.nextGenFormat }-delete-all-notice`;
		const deletedAllNoticeElement = document.getElementById( deletedAllNoticeElementID );
		if ( ! deletedAllNoticeElement ) {
			return;
		}
		const noticeMessage = `<p>${
			deletedAllNoticeElement
				.dataset.message
		}</p>`;

		const noticeOptions = {
			type: 'success',
			icon: 'check-tick',
			dismiss: {
				show: true,
			},
		};

		window.SUI.openNotice(
			deletedAllNoticeElementID,
			noticeMessage,
			noticeOptions
		);
	}

	onSavedNextGenSettingsHandler( status ) {
		if ( 'next-gen' === status?.detail?.page ) {
			if ( status?.detail?.next_gen_format_changed ) {
				this.redirectToNextGenPage( '&smush-notice=next-gen-conversion-changed' );
			} else if ( status?.detail?.webp_method_changed ) {
				this.redirectToNextGenPage();
			}
		}
	}

	showNextGenActivatedModal() {
		if ( ! window.WP_Smush ) {
			return;
		}

		const activatedModalId = 'smush-next-gen-activated-modal';
		if ( ! document.getElementById( activatedModalId ) ) {
			this.redirectToNextGenPage();
			return;
		}

		window.WP_Smush.helpers.showModal( activatedModalId, {
			isCloseOnEsc: true,
		} );
	}

	showNextGenConversionChangedModal() {
		if ( ! window.WP_Smush ) {
			return;
		}

		const conversionChangedModalId = 'smush-next-gen-conversion-changed-modal';
		if ( ! document.getElementById( conversionChangedModalId ) ) {
			this.redirectToNextGenPage();
			return;
		}

		window.WP_Smush.helpers.showModal( conversionChangedModalId, {
			isCloseOnEsc: true,
		} );
	}

	redirectToNextGenPage( noticeParam ) {
		window.location.href = window.wp_smush_msgs.nextGenURL + ( noticeParam || '' );
	}
}

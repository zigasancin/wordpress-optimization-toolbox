/**
 * CDN functionality.
 *
 * @since 3.0
 */
( function() {
	'use strict';

	WP_Smush.CDN = {
		cdnEnableButton: document.getElementById('smush-enable-cdn'),
		cdnDisableButton: document.getElementById('smush-cancel-cdn'),
		cdnStatsBox: document.querySelector('.smush-cdn-stats'),

		init: function () {
			/**
			 * Handle "Get Started" button click on disabled CDN page.
			 */
			if ( this.cdnEnableButton ) {
				this.cdnEnableButton.addEventListener('click', (e) => {
					e.currentTarget.classList.add('sui-button-onload');

					// Force repaint of the spinner.
					const loader = e.currentTarget.querySelector('.sui-icon-loader');
					loader.style.display = 'none';
					loader.offsetHeight;
					loader.style.display = 'flex';

					this.toggle_cdn(true);
				});
			}

			/**
			 * Handle "Deactivate' button click on CDN page.
			 */
			if ( this.cdnDisableButton ) {
				this.cdnDisableButton.addEventListener('click', (e) => {
					e.preventDefault();
					this.toggle_cdn(false);
				});
			}

			this.updateStatsBox();
		},

		/**
		 * Toggle CDN.
		 *
		 * @since 3.0
		 *
		 * @param enable
		 */
		toggle_cdn: function ( enable ) {
			const nonceField = document.getElementsByName('wp_smush_options_nonce');

			fetch(ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
				},
				body: 'action=smush_toggle_cdn&param=' + enable + '&_ajax_nonce=' + nonceField[0].value
			})
			.then(data => {
				const response = data.json();
				response.then(res => {
					if ( 'undefined' !== typeof res.success && res.success ) {
						location.reload();
					} else if ( 'undefined' !== typeof res.data.message ) {
						this.showNotice( res.data.message );
					}
				});

			})
			.catch( error => console.error(error) );
		},

		/**
		 * Show message (notice).
		 *
		 * @since 3.0
		 *
		 * @param {string} message
		 */
		showNotice: function ( message ) {
			if ( 'undefined' === typeof message ) {
				return;
			}

			const notice = document.getElementById('wp-smush-ajax-notice');

			notice.classList.add('sui-notice-error');
			notice.innerHTML = `<p>${message}</p>`;

			if ( this.cdnEnableButton ) {
				this.cdnEnableButton.classList.remove('sui-button-onload');
			}

			notice.style.display = 'block';
			setTimeout( () => { notice.style.display = 'none' }, 5000 );
		},

		/**
		 * Update the CDN stats box in summary meta box. Only fetch new data when on CDN page.
		 *
		 * @since 3.0
		 */
		updateStatsBox: function () {
			if ( 'undefined' === typeof this.cdnStatsBox || ! this.cdnStatsBox ) {
				return;
			}

			// Only fetch the new stats, when user is on CDN page.
			if ( ! window.location.search.includes('view=cdn') ) {
				return;
			}

			const spinner = this.cdnStatsBox.querySelector('.sui-icon-loader');
			const elements = this.cdnStatsBox.querySelectorAll('.wp-smush-stats > :not(.sui-icon-loader)');

			elements.forEach(element => element.classList.toggle('sui-hidden'));
			spinner.classList.toggle('sui-hidden');

			fetch(ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
				},
				body: 'action=get_cdn_stats'
			})
			.then(data => {
				const response = data.json();
				response.then(res => {
					if ( 'undefined' !== typeof res.success && res.success ) {
						/**
						 * TODO: It's possible to parse the res variable and update the latest stats during the update,
						 * but is it really needed?
						 */
						elements.forEach(element => element.classList.toggle('sui-hidden'));
						spinner.classList.toggle('sui-hidden');
					} else if ( 'undefined' !== typeof res.data.message ) {
						this.showNotice( res.data.message );
					}
				});
			})
			.catch( error => console.error(error) );
		}

	};

	WP_Smush.CDN.init();

}());
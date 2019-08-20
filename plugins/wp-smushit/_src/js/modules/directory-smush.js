/**
 * Directory Smush module JavaScript code.
 *
 * @since 2.8.1  Separated from admin.js into dedicated file.
 */

import { createTree } from 'jquery.fancytree';
import Scanner from '../smush/directory-scanner';

( function( $ ) {
	'use strict';

	WP_Smush.directory = {
		selected: [],
		tree: [],
		wp_smush_msgs: [],

		init: function () {
			const self = this;

			let progress_dialog = $('#wp-smush-progress-dialog'),
				totalSteps = 0,
				currentScanStep = 0;

			// Make sure directory smush vars are set.
			if ( typeof wp_smushit_data.dir_smush !== 'undefined' ) {
				totalSteps = wp_smushit_data.dir_smush.totalSteps;
				currentScanStep = wp_smushit_data.dir_smush.currentScanStep;
			}

			// Init image scanner.
			this.scanner = new Scanner( totalSteps, currentScanStep );

			/**
			 * Smush translation strings.
			 *
			 * @var {array} wp_smush_msgs
			 */
			if ( wp_smush_msgs ) {
				this.wp_smush_msgs = wp_smush_msgs;
			}

			/**
			 * Folder select: Choose Folder in Directory Smush tab clicked.
			 */
			$( 'div.sui-wrap' ).on( 'click', 'button.wp-smush-browse', function ( e ) {
				e.preventDefault();

				// Hide all the notices.
				$( 'div.wp-smush-scan-result div.wp-smush-notice' ).hide();

				// If disabled, do not process.
				if ( $( this ).attr( 'disabled' ) ) {
					return;
				}

				// Disable buttons.
				$( this ).attr( 'disabled', 'disabled' );

				// Remove notice.
				$( 'div.wp-smush-info' ).remove();

				self.showSmushDialog();

				// Display file tree for directory Smush.
				self.initFileTree();
			} );

			/**
			 * Stats section: Directory Link
			 */
			$( 'body' ).on( 'click', 'a.wp-smush-dir-link', function ( e ) {
				if ( $( 'div.sui-wrap button.wp-smush-browse' ).length > 0 ) {
					e.preventDefault();
					self.showSmushDialog();
					//Display File tree for Directory Smush
					self.initFileTree();
				}
			} );

			/**
			 * Smush images: Smush in Choose Directory modal clicked
			 */
			$( '.wp-smush-select-dir' ).on( 'click', function ( e ) {
				e.preventDefault();

				// If disabled, do not process
				if ( $( this ).attr( 'disabled' ) ) {
					return;
				}

				const button = $( this );

				$( 'div.wp-smush-list-dialog div.sui-box-body' ).css( { 'opacity': '0.8' } );
				$( 'div.wp-smush-list-dialog div.sui-box-body a' ).unbind( 'click' );

				// Disable button
				button.attr( 'disabled', 'disabled' );

				let spinner = button.parent().find( '.add-dir-loader' );
				// Display the spinner
				spinner.addClass( 'sui-icon-loader sui-loading' );

				const selectedFolders = self.tree.getSelectedNodes(),
				      abs_path        = $( 'input[name="wp-smush-base-path"]' ).val(); // Absolute path.

				let paths = [];
				selectedFolders.forEach( function ( folder ) {
					paths.push( abs_path + '/' + folder.key );
				});

				// Send a ajax request to get a list of all the image files
				const param = {
					action: 'image_list',
					smush_path: paths,
					image_list_nonce: $( 'input[name="image_list_nonce"]' ).val()
				};

				$.get( ajaxurl, param, function ( response ) {
					self.hideSmushDialog();

					// TODO: check for errors.
					self.scanner = new Scanner( response.data, 0 );
					self.showProgressDialog( response.data );
					self.scanner.scan();
				} );
			} );

			/**
			 * On dialog close make browse button active.
			 */
			$( '#wp-smush-list-dialog' ).on( 'click', '.sui-dialog-close', function () {
				$( '.wp-smush-browse' ).removeAttr( 'disabled' );

				// Close the dialog.
				self.hideSmushDialog();

				$( '.wp-smush-select-dir, button.wp-smush-browse, a.wp-smush-dir-link' ).removeAttr( 'disabled' );

				// Reset the opacity for content and scan button
				$( '.wp-smush-select-dir, .wp-smush-list-dialog .sui-box-body' ).css( {'opacity': '1'} );
			} );

			/**
			 * Cancel scan.
			 */
			progress_dialog.on( 'click', '#cancel-directory-smush, .sui-dialog-close, .wp-smush-cancel-dir', function ( e ) {
				e.preventDefault();
				// Display the spinner
				$( this ).parent().find( '.add-dir-loader' ).addClass( 'sui-icon-loader sui-loading' );
				self.scanner.cancel().done( () => window.location.href = self.wp_smush_msgs.directory_url );
			} );

			/**
			 * Continue scan.
			 */
			progress_dialog.on( 'click', '.sui-icon-play, .wp-smush-resume-scan', function ( e ) {
				e.preventDefault();
				self.scanner.resume();
			} );
		},

		/**
		 * Init fileTree.
		 */
		initFileTree: function () {
			const self = this;

			let smushButton = $( 'button.wp-smush-select-dir' );

			let ajaxSettings = {
				type: "GET",
				url: ajaxurl,
				data: {
					action: 'smush_get_directory_list',
					list_nonce: $( 'input[name="list_nonce"]' ).val()
				},
				cache: false
			};

			self.tree = createTree('.wp-smush-list-dialog .content', {
				autoCollapse: true, // Automatically collapse all siblings, when a node is expanded
				clickFolderMode: 3, // 1:activate, 2:expand, 3:activate and expand, 4:activate (dblclick expands)
				checkbox: true,     // Show checkboxes
				debugLevel: 0,      // 0:quiet, 1:errors, 2:warnings, 3:infos, 4:debug
				selectMode: 3,      // 1:single, 2:multi, 3:multi-hier
				tabindex: '0',      // Whole tree behaves as one single control
				keyboard: true,     // Support keyboard navigation
				quicksearch: true,  // Navigate to next node by typing the first letters
				source: ajaxSettings,
				lazyLoad: ( event, data ) => {
					data.result = new Promise(function( resolve, reject ) {
						ajaxSettings.data.dir = data.node.key;
						$.ajax( ajaxSettings )
							.done( response => resolve( response ) )
							.fail( reject );
					});

					// Update the button text.
					data.result.then( smushButton.html( self.wp_smush_msgs.add_dir ) );
				},
				loadChildren: ( event, data ) => data.node.fixSelection3AfterClick(), // Apply parent's state to new child nodes:
				select: () => smushButton.attr( 'disabled', !+self.tree.getSelectedNodes().length ),
				init: () => smushButton.attr( 'disabled', true ),
			});
		},

		/**
		 * Show directory list popup and focus on close button.
		 */
		showSmushDialog: function () {
			// Shows the available directories.
			const el = document.getElementById('wp-smush-list-dialog');
			const dialog = new A11yDialog(el);
			dialog.show();

			$( '.wp-smush-list-dialog div.close' ).focus();
		},

		/**
		 * Hide directory list popup.
		 * @since 3.2.2
		 */
		hideSmushDialog: function() {
			const el = document.getElementById('wp-smush-list-dialog');
			const dialog = new A11yDialog(el);
			dialog.hide();

			const progressDialog = new A11yDialog(document.getElementById('wp-smush-progress-dialog'));
			progressDialog.hide();
		},

		/**
		 * Show progress dialog.
		 *
		 * @param {int} items  Number of items in the scan.
		 */
		showProgressDialog: function ( items ) {
			// Update items status and show the progress dialog..
			$( '.wp-smush-progress-dialog .sui-progress-state-text' ).html( '0/' + items + ' ' + self.wp_smush_msgs.progress_smushed );

			const el = document.getElementById('wp-smush-progress-dialog');
			const dialog = new A11yDialog(el);
			dialog.show();

			$( '.wp-smush-progress-dialog div.close' ).focus();
		},

		/**
		 * Update progress bar during directory smush.
		 *
		 * @param {int}     progress  Current progress in percent.
		 * @param {boolean} cancel    Cancel status.
		 */
		updateProgressBar: function ( progress, cancel = false ) {
			if ( progress > 100 ) {
				progress = 100;
			}

			// Update progress bar
			$( '.sui-progress-block .sui-progress-text span' ).text( progress + '%' );
			$( '.sui-progress-block .sui-progress-bar span' ).width( progress + '%' );

			if ( progress >= 90 ) {
				$( '.sui-progress-state .sui-progress-state-text' ).text( 'Finalizing...' );
			}

			if ( cancel ) {
				$( '.sui-progress-state .sui-progress-state-text' ).text( 'Cancelling...' );
			}
		},

	};

	WP_Smush.directory.init();

}( jQuery ));

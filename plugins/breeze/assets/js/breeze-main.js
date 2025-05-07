jQuery( document ).ready(
	function ( $ ) {

		var $box_container = $( '.breeze-box' );

		var $compatibility_warning = $( '#breeze-plugins-notice' );
		if ( $compatibility_warning.length ) {
			$( document ).on(
				'click tap',
				'.notice-dismiss',
				function () {
					$.ajax(
						{
							type: "POST",
							url: ajaxurl,
							data: { action: "compatibility_warning_close", 'breeze_close_warning': '1' },
							dataType: "json", // xml, html, script, json, jsonp, text
							success: function ( data ) {

							},
							error: function ( jqXHR, textStatus, errorThrown ) {

							},
							// called when the request finishes (after success and error callbacks are executed)
							complete: function ( jqXHR, textStatus ) {

							}
						}
					);
				}
			);
		}

		$( document ).on(
			'click',
			'.rollback-button',
			function (e) {
				e.preventDefault();
				var selectedVersion = $( '.breeze-version' ).val();
				// Display form submit confirmation dialog
				var confirmation = confirm( "Want to rollback version " + selectedVersion + " ?" );

				// If user confirms, submit the form
				if (confirmation) {
					document.getElementById( "breeze_rollback_form" ).submit();
				}
			}
		);

		// Top bar action
		$( document ).on(
			'click',
			'#wp-admin-bar-breeze-purge-varnish-group',
			function ( e ) {
				e.preventDefault();
				breeze_purgeVarnish_callAjax();
			}
		);
		// Topbar action
		$( document ).on(
			'click',
			'#wp-admin-bar-breeze-purge-object-cache-group',
			function ( e ) {
				e.preventDefault();
				breeze_purge_opcache_ajax();
			}
		);

		$( document ).on(
			'click',
			'#wp-admin-bar-breeze-purge-file-group',
			function ( e ) {
				e.preventDefault();
				breeze_purgeFile_callAjax();
			}
		);

		// Reset Default
		$( document ).on(
			'click',
			'#breeze_reset_default',
			function ( e ) {
				e.preventDefault();

				reset_confirm = confirm( "Want to reset breeze settings?" );

				if ( reset_confirm ) {

					breeze_reset_default();
				}
			}
		);

		var purge_action = true;
		// Varnish clear button
		$( '.breeze-box' ).on(
			'click',
			'#purge-varnish-button',
			function ( e ) {
				e.preventDefault();

				if ( true === purge_action ) {
					purge_action = false;
					$( this ).addClass( 'br-is-disabled' );
					breeze_purgeVarnish_callAjax();
				}

			}
		);

		if ( $box_container.length ) {
			$( '.breeze-box' ).on(
				'keyup paste',
				'#cdn-url',
				function () {
					var cdn_value = $.trim( $( this ).val() );
					if ( '' !== cdn_value && true === is_valid_url( cdn_value ) ) {

						$.ajax(
							{
								type: "POST",
								url: ajaxurl,
								data: {
									action: 'breeze_check_cdn_url',
									'cdn_url': cdn_value,
									security: breeze_token_name.breeze_check_cdn_url
								},
								dataType: "json", // xml, html, script, json, jsonp, text
								success: function ( data ) {
									if ( false === data.success ) {
										$( '#cdn-message-error' ).show();
										$( '#cdn-message-error' ).html( data.message );
									} else {
										$( '#cdn-message-error' ).hide();
									}
								},
								error: function ( jqXHR, textStatus, errorThrown ) {

								},
								// called when the request finishes (after success and error callbacks are executed)
								complete: function ( jqXHR, textStatus ) {

								}
							}
						);
					} else {
						$( '#cdn-message-error' ).hide();
					}
				}
			);
		}

		function is_valid_url( url ) {
			return /^(http(s)?:)?\/\/(www\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test( url );
		}

		//clear cache by button
		function breeze_purge_opcache_ajax() {
			$( '.br-internal-purge' ).remove();
			$( '.breeze-notice' ).remove();
			$.ajax(
				{
					url: ajaxurl,
					dataType: 'json',
					method: 'POST',
					data: {
						action: 'breeze_purge_opcache',
						is_network: $( 'body' ).hasClass( 'network-admin' ),
						security: breeze_token_name.breeze_purge_opcache
					},
					success: function ( res ) {
						current = location.href;
						if ( res.clear ) {
							var div = '<div id="message" class="notice notice-success is-dismissible breeze-notice" style="margin-top:10px; margin-bottom:10px;padding: 10px;margin-left: 0;"><p><strong>Object Cache has been purged.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
							//backend
							$( "#wpbody #wpbody-content" ).prepend( div );
							setTimeout(
								function () {
									//location.reload();
									purge_action = true;
								},
								2000
							);

						} else {
							window.location.href = current + "breeze-msg=purge-fail";
							purge_action         = true;
							location.reload();
						}
					}
				}
			);
		}

		//reset to default
		function breeze_reset_default() {
			$(
				'<div/>',
				{
					'id': 'breeze_loader_function'
				}
			).appendTo( 'body' );

			$(
				'<div/>',
				{
					'id': 'breeze_info',
					'html': '<span class="breeze-ajax-loader"></span>'
				}
			).appendTo( 'body' );

			$.ajax(
				{
					type: "POST",
					url: ajaxurl,
					data: {
						action: 'breeze_reset_default',
						"is-network": $( 'body' ).hasClass( 'network-admin' ),
						security: breeze_token_name.breeze_reset_default
					},
					dataType: "json", // xml, html, script, json, jsonp, text
					success: function ( data ) {
						if ( data === true ) {
							//alert('Settings reset to default');
							purge_action = true;
						} else {
							alert( 'Something went wrong - please try again' );
						}

					},
					error: function ( jqXHR, textStatus, errorThrown ) {

					},
						// called when the request finishes (after success and error callbacks are executed)
					complete: function ( jqXHR, textStatus ) {
						location.reload();
					}
				}
			);

		}

		//clear cache by button
		function breeze_purgeVarnish_callAjax() {
			$( '.br-internal-purge' ).remove();
			$( '.breeze-notice' ).remove();
			$.ajax(
				{
					url: ajaxurl,
					dataType: 'json',
					method: 'POST',
					data: {
						action: 'breeze_purge_varnish',
						is_network: $( 'body' ).hasClass( 'network-admin' ),
						security: breeze_token_name.breeze_purge_varnish
					},
					success: function ( res ) {
						current = location.href;
						if ( res.clear ) {
							var div = '<div id="message" class="notice notice-success is-dismissible breeze-notice" style="margin-top:10px; margin-bottom:10px;padding: 10px;margin-left: 0;"><p><strong>Varnish Cache has been purged.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
							//backend
							$( "#wpbody #wpbody-content" ).prepend( div );
							setTimeout(
								function () {
									//location.reload();
									purge_action = true;
								},
								2000
							);

						} else {
							window.location.href = current + "breeze-msg=purge-fail";
							purge_action         = true;
							location.reload();
						}
					}
				}
			);
		}

		function breeze_purgeFile_callAjax() {
			$( '.br-internal-purge' ).remove();
			$( '.breeze-notice' ).remove();
			$.ajax(
				{
					url: ajaxurl,
					dataType: 'json',
					method: 'POST',
					data: {
						action: 'breeze_purge_file',
						security: breeze_token_name.breeze_purge_cache
					},
					success: function ( res ) {
						current       = location.href;
						res           = parseFloat( res );
						var fileClean = res;

						// Remove the hash fragment (everything after #) from the current URL to avoid duplicates
						//if ( current.includes( "#" ) ) {
						//	current = current.split( "#" )[ 0 ];
						//}
						//window.location.href = current + "#breeze-msg=success-cleancache&file=" + res;
						//location.reload();
						if ( fileClean > 0 ) {
							div = '<div id="message" class="notice notice-success is-dismissible breeze-notice br-internal-purge" style="margin-top:10px; margin-bottom:10px;padding: 10px;margin-left: 0;"><p><strong>Internal cache has been purged: ' + fileClean + 'Kb cleaned</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
						} else {
							div = '<div id="message" class="notice notice-success is-dismissible breeze-notice br-internal-purge" style="margin-top:10px; margin-bottom:10px;padding: 10px;margin-left: 0;"><p><strong>Internal cache has been purged.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

						}
						$( "#wpbody #wpbody-content" ).prepend( div );

					}
				}
			);
		}

		function getParameterByName( name, url ) {
			if ( ! url ) {
				url = window.location.href;
			}
			name        = name.replace( /[\[\]]/g, "\\$&" );
			var regex   = new RegExp( "[?&]" + name + "(=([^&#]*)|&|#|$)" ),
				results = regex.exec( url );
			if ( ! results ) {
				return null;
			}
			if ( ! results[ 2 ] ) {
				return '';
			}
			return decodeURIComponent( results[ 2 ].replace( /\+/g, " " ) );
		}

		var url       = location.href;
		var fileClean = parseFloat( getParameterByName( 'file', url ) );

		$( window ).on(
			'load',
			function () {
				var patt = /wp-admin/i;
				if ( patt.test( url ) ) {
					//backend
					var div = '';
					if ( url.indexOf( "msg=success-cleancache" ) > 0 && ! isNaN( fileClean ) ) {
						if ( fileClean > 0 ) {
							div = '<div id="message" class="notice notice-success is-dismissible breeze-notice" style="margin-top:10px; margin-bottom:10px;padding: 10px;"><p><strong>Internal cache has been purged: ' + fileClean + 'Kb cleaned</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
						} else {
							div = '<div id="message" class="notice notice-success is-dismissible breeze-notice" style="margin-top:10px; margin-bottom:10px;padding: 10px;"><p><strong>Internal cache has been purged.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

						}

						$( "#wpbody .wrap h1" ).after( div );

						var url_return = url.split( 'breeze-msg' );
						setTimeout(
							function () {
								window.location = url_return[ 0 ];
								//location.reload();
							},
							2000
						);
					}
				} else {
					//frontend
				}

			}
		);

		$( '#breeze-hide-install-msg' ).unbind( 'click' ).click(
			function () {
				$( this ).closest( 'div.notice' ).fadeOut();
			}
		)

		function current_url_clean() {
			var query_search = location.search;
			if ( ( query_search.indexOf( 'breeze_purge=1' ) !== -1 || query_search.indexOf( 'breeze_purge_cloudflare=1' ) !== -1 ) && query_search.indexOf( '_wpnonce' ) !== -1 ) {
				var params = new URLSearchParams( location.search );
				params.delete( 'breeze_purge' )
				params.delete( 'breeze_purge_cloudflare' )
				params.delete( '_wpnonce' )
				history.replaceState( null, '', '?' + params + location.hash )
			}
		}

		current_url_clean();

		// Advanced options, API tab
		$box_container.on(
			'change',
			'#breeze-enable-api',
			function () {
				var secure_api = $( '#breeze-secure-api' );
				var token_api  = $( '#breeze-api-token' );
				//var api_route = $( '#breeze-secure-api' );

				if ( $( this ).is( ':checked' ) ) {
					secure_api.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
					token_api.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
				} else {
					secure_api.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					token_api.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );

					secure_api.prop( 'checked', false );
					//token_api.trigger( 'change' );
				}
			}
		);

		$box_container.on(
			'change',
			'#bz-lazy-load',
			function () {

				var native_lazy         = $( '#native-lazy-option' );
				var native_lazy_iframes = $( '#native-lazy-option-iframe' );
				var native_lazy_video   = $( '#native-lazy-option-videos' );
				if ( true === $( this ).is( ':checked' ) ) {
					native_lazy.show();
					native_lazy_iframes.show();
					native_lazy_video.show();
				} else {
					native_lazy.hide();
					native_lazy_iframes.hide();
					native_lazy_video.hide();
					$( '#bz-lazy-load-nat' ).attr( 'checked', false );
					$( '#bz-lazy-load-iframe' ).attr( 'checked', false );
					$( '#bz-lazy-load-videos' ).attr( 'checked', false );
				}
			}
		);
		/*
		 var font_display_swap = $( '#font-display-swap' );
		 var font_display      = $( '#font-display' );
		 var css_minification  = $( '#minification-css' );

		 if ( css_minification.is( ':checked' ) ) {
		 font_display_swap.show();
		 } else {
		 font_display_swap.hide();
		 font_display.attr( 'checked', false );
		 }
		 */

		$box_container.on(
			'change',
			'#minification-css',
			function () {
				var font_display_swap = $( '#font-display-swap' );
				var font_display      = $( '#font-display' );

				var include_inline_css = $( '#include-inline-css' );
				var group_css          = $( '#group-css' );
				var minification_css   = $( '#exclude-css' );

				if ( $( this ).is( ':checked' ) ) {
					font_display_swap.show();
					//include_inline_css.removeAttr( 'disabled' );
					//group_css.removeAttr( 'disabled' );

					minification_css.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
					group_css.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
					include_inline_css.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
				} else {
					font_display_swap.hide();
					font_display.removeAttr( 'checked' );
					//include_inline_css.removeAttr( 'checked' ).attr( 'disabled', 'disabled' );
					//group_css.removeAttr( 'checked' ).attr( 'disabled', 'disabled' );
					include_inline_css.prop( 'checked', false );
					group_css.prop( 'checked', false );

					minification_css.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					group_css.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					include_inline_css.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
				}
			}
		);

		$box_container.on(
			'change',
			'#minification-js',
			function () {

				var include_inline_js = $( '#include-inline-js' );
				var group_js          = $( '#group-js' );
				var exclude_js        = $( '#exclude-js' );
				var delay_js_scripts  = $( '#enable-js-delay' ); // Delay JS Inline Scripts
				var enable_js_delay   = $( '#breeze-delay-all-js' ); // Delay All JavaScript

				if ( $( this ).is( ':checked' ) ) {
					//include_inline_js.removeAttr( 'disabled' );
					//group_js.removeAttr( 'disabled' );

					exclude_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
					if ( include_inline_js.is( "checked" ) ) {
						if ( ! delay_js_scripts.is( ':checked' ) && ! enable_js_delay.is( ':checked' ) ) {

						}
					}
					group_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' ); // breeze 194
					include_inline_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
				} else {
					//include_inline_js.removeAttr( 'checked' ).attr( 'disabled', 'disabled' );
					//group_js.removeAttr( 'checked' ).attr( 'disabled', 'disabled' );
					include_inline_js.prop( 'checked', false );
					group_js.prop( 'checked', false );
					group_js.trigger( 'change' );

					exclude_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					group_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					include_inline_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
				}
			}
		);

		/**
		 * Breeze 194
		 */
		// $box_container.on(
		// 	'change',
		// 	'#include-inline-js',
		// 	function () {
		// 		var js_minification = $( '#minification-js' );
		// 		var delay_js_scripts = $( '#enable-js-delay' ); // Delay JS Inline Scripts
		// 		var enable_js_delay = $( '#breeze-delay-all-js' ); // Delay All JavaScript
		// 		var group_js        = $( '#group-js' );
		// 		if ( js_minification.is( ':checked' ) ) {
		// 			if ( !delay_js_scripts.is( ':checked' ) && !enable_js_delay.is( ':checked' ) ) {
		// 			group_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
		// 		}
		// 		}
		//
		// 		if ( $( this ).is( ':checked' ) ) {
		// 			if ( !delay_js_scripts.is( ':checked' ) && !enable_js_delay.is( ':checked' ) ) {
		// 			group_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
		// 			}
		// 		} else {
		// 			group_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
		// 			group_js.prop( 'checked', false );
		// 		}
		// 	}
		// );

		$box_container.on(
			'change',
			'#group-js',
			function () {

				var delay_js_scripts = $( '#enable-js-delay' ); // Delay JS Inline Scripts
				var enable_js_delay  = $( '#breeze-delay-all-js' ); // Delay All JavaScript

				if ( $( this ).is( ':checked' ) ) {
					delay_js_scripts.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					delay_js_scripts.prop( 'checked', false );

					enable_js_delay.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					enable_js_delay.prop( 'checked', false );
				} else {
					delay_js_scripts.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
					enable_js_delay.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
				}
			}
		);

		$box_container.on(
			'change',
			'#breeze-delay-all-js',
			function () {

				var group_js             = $( '#group-js' );
				var $delay_js_div_all    = $( '#breeze-delay-js-scripts-div-all' );
				var $enable_inline_delay = $( '#enable-js-delay' );

				if ( $( this ).is( ':checked' ) ) {
					$delay_js_div_all.show();
					$( 'input[name="enable-js-delay"]' ).prop( 'checked', false );
					$( '#breeze-delay-js-scripts-div' ).hide();
					$enable_inline_delay.attr( 'disabled', 'disabled' );
					group_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					group_js.prop( 'checked', false );
				} else {
					$delay_js_div_all.hide();
					$enable_inline_delay.removeAttr( 'disabled' );
					group_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
				}
			}
		)

		$box_container.on(
			'change',
			'#enable-js-delay',
			function () {
				var $delay_js_div = $( '#breeze-delay-js-scripts-div' );
				var $delay_all_js = $( '#breeze-delay-all-js' );
				var group_js      = $( '#group-js' );

				if ( $( this ).is( ':checked' ) ) {
					$delay_js_div.show();
					$( 'input[name="breeze-delay-all-js"]' ).prop( 'checked', false );
					$( '#breeze-delay-js-scripts-div-all' ).hide();
					$delay_all_js.attr( 'disabled', 'disabled' );
					group_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
					group_js.prop( 'checked', false );
				} else {
					$delay_js_div.hide();
					$delay_all_js.removeAttr( 'disabled' );
					group_js.closest( 'div.br-option-item' ).removeClass( 'br-apply-disable' );
				}
			}
		)
	}
);

var $valid_json = false;
jQuery( document ).ready(
	function ( $ ) {
		var $tab_import = $( '.breeze-box' );
		// database clean tabs
		$( 'input[name="all_control"]' ).click(
			function () {
				var checked = $( this ).is( ':checked' );
				if ( checked == true ) {
					$( ".clean-data" ).prop( "checked", true );
				} else {
					$( ".clean-data" ).prop( "checked", false );
				}
			}
		);

		$( '.clean-data' ).click(
			function () {
				var checked = $( this ).is( ':checked' );
				if ( checked == false ) {
					$( 'input[name="all_control"]' ).prop( 'checked', false );
				}
			}
		);

		function initRemoveBtn() {
			$tab_import.on(
				'click',
				'span.item-remove',
				function(){
					var inputURL = $( this ).closest( '.breeze-input-group' );
					inputURL.fadeOut(
						300,
						function () {
							inputURL.remove();
							validateMoveButtons();
						}
					);
				}
			);

		}

		initRemoveBtn();

		function initSortableHandle() {

			if ( $( '.breeze-list-url' ).length ) {
				$( '.breeze-list-url' ).sortable(
					{
						handle: $( 'span.sort-handle' ),
						stop: validateMoveButtons
					}
				);
			}
		}

		initSortableHandle();

		function initMoveButtons() {
			$( '.sort-handle span' ).unbind( 'click' ).click(
				function ( e ) {
					var inputGroup = $( this ).parents( '.breeze-input-group' );
					if ( $( this ).hasClass( 'moveUp' ) ) {
						inputGroup.insertBefore( inputGroup.prev() );
					} else {
						inputGroup.insertAfter( inputGroup.next() );
					}

					validateMoveButtons();
				}
			);
		}

		initMoveButtons();

		function validateMoveButtons() {
			var listURL = $( '.breeze-list-url' );
			listURL.find( '.breeze-input-group' ).find( '.sort-handle' ).find( 'span' ).removeClass( 'blur' );
			listURL.find( '.breeze-input-group:first-child' ).find( '.moveUp' ).addClass( 'blur' );
			listURL.find( '.breeze-input-group:last-child' ).find( '.moveDown' ).addClass( 'blur' );
		}

		validateMoveButtons();

		function is_valid_url(str) {
			var regexp = /^(?:(?:https?|ftp):\/\/)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/\S*)?$/;
			if (regexp.test( str )) {
				return true;
			} else {
				return false;
			}
		}
		$tab_import.on(
			'keyup change blur',
			'.breeze-input-url',
			function(){
				var url_value = $( this ).val();
				if ('' !== url_value) {
					var is_valid = is_valid_url( url_value );
					if (true === is_valid) {
						$( this ).removeClass( 'is-invalid-url' )
					} else {
						$( this ).addClass( 'is-invalid-url' );
					}
				}
			}
		);

		//$( 'button.add-url' ).unbind( 'click' ).click(
		$tab_import.on(
			'click',
			'button.add-url',
			function () {

				var defer      = $( this ).attr( 'id' ).indexOf( 'defer' ) > -1;
				var preload    = $( this ).attr( 'id' ).indexOf( 'preload-fonts' ) > -1;
				var listURL    = $( this ).closest( 'div.br-option' ).find( '.breeze-list-url' );
				var html       = '';
				var listInput  = listURL.find( '.breeze-input-group' );
				var emptyInput = false;

				listInput.each(
					function () {
						var thisInput = $( this ).find( '.breeze-input-url' );
						if ( thisInput.val().trim() === '' ) {
							thisInput.focus();
							emptyInput = true;
							return false;
						}
					}
				);

				if ( emptyInput ) {
					return false;
				}

				html += '<div class="breeze-input-group">';
				html += '   <input type="text" size="98"';
				html += 'class="breeze-input-url"';
				if ( preload ) {
					html += 'name="breeze-preload-font[]"';
				} else if ( ! defer ) {
					html += 'name="move-to-footer-js[]"';
				} else {
					html += 'name="defer-js[]"';
				}
				html += 'placeholder="Enter URL..."';
				html += 'value="" />';
				html += '   <span class="sort-handle">';
				html += '       <span class="dashicons dashicons-arrow-up moveUp"></span>';
				html += '       <span class="dashicons dashicons-arrow-down moveDown"></span>';
				html += '   </span>';
				html += '       <span class="dashicons dashicons-no item-remove" title="Remove"></span>';
				html += '</div>';

				listURL.append( html );
				initRemoveBtn();
				initSortableHandle();
				initMoveButtons();
				validateMoveButtons();
			}
		);

		// Change tab // TODO REMOVE
		$( "#breeze-tabs .nav-tab" ).click(
			function ( e ) {
				e.preventDefault();
				$( "#breeze-tabs .nav-tab" ).removeClass( 'active' );
				$( e.target ).addClass( 'active' );
				id_tab = $( this ).data( 'tab-id' );
				$( "#tab-" + id_tab ).addClass( 'active' );
				$( "#breeze-tabs-content .tab-pane" ).removeClass( 'active' );
				$( "#tab-content-" + id_tab ).addClass( 'active' );
				document.cookie = 'breeze_active_tab=' + id_tab;

				// Toggle right-side content
				if ( id_tab === 'faq' ) {
					$( '#breeze-and-cloudways' ).hide();
					if ( $( '#faq-content' ).length ) {
						$( '#faq-content' ).accordion(
							{
								collapsible: true,
								animate: 200,
								header: '.faq-question',
								heightStyle: 'content'
							}
						);
					}
				} else {
					$( '#breeze-and-cloudways' ).show();
				}
			}
		);

		// Cookie do
		function Breeze_setTabFromCookie() {
			var breeze_active_tab = getCookie( 'breeze_active_tab' );
			if ( ! breeze_active_tab ) {
				breeze_active_tab = 'basic';
			}

			if ('import_export' === breeze_active_tab) {
				breeze_active_tab = 'basic';
			}

			if ( $( "#tab-" + breeze_active_tab ).length === 0 ) { // Tab not found (multisite case)
				firstTab = $( '#breeze-tabs' ).find( 'a:first-child' );
				if (firstTab.length) {
					tabType = firstTab.attr( 'id' ).replace( 'tab-', '' );
					firstTab.addClass( 'active' );
					$( "#tab-content-" + tabType ).addClass( 'active' );
				}
			} else {
				$( "#tab-" + breeze_active_tab ).addClass( 'active' );
				$( "#tab-content-" + breeze_active_tab ).addClass( 'active' );
			}

			// Toggle right-side content
			if ( breeze_active_tab === 'faq' ) {
				$( '#breeze-and-cloudways' ).hide();
				if ( $( '#faq-content' ).length ) {
					$( '#faq-content' ).accordion(
						{
							collapsible: true,
							animate: 200,
							header: '.faq-question',
							heightStyle: 'content'
						}
					);
				}
			} else {
				$( '#breeze-and-cloudways' ).show();
			}
		}

		function getCookie( cname ) {
			var name = cname + "=";
			var ca   = document.cookie.split( ';' );
			for ( var i = 0; i < ca.length; i++ ) {
				var c = ca[ i ];
				while ( c.charAt( 0 ) == ' ' ) {
					c = c.substring( 1 );
				}
				if ( c.indexOf( name ) == 0 ) {
					return c.substring( name.length, c.length );
				}
			}
			return "";
		}

		Breeze_setTabFromCookie();

		// Sub-site settings toggle.
		var global_tabs                          = [
			'faq'
		];
		var save_settings_inherit_form_on_submit = true;
		var settings_inherit_form_did_change     = false;
		var $settings_inherit_form               = $( '#breeze-inherit-settings-toggle' );
		if ( $settings_inherit_form.length ) {
			$( 'input', $settings_inherit_form ).on(
				'change',
				function () {
					var inherit = $( this ).val() == '1';

					$( '#breeze-tabs' ).toggleClass( 'tabs-hidden', inherit );
					$( '#breeze-tabs-content' ).toggleClass( 'tabs-hidden', inherit );

					$( '#breeze-tabs .nav-tab' ).each(
						function () {
							var tab_id = $( this ).data( 'tab-id' );

							if ( $.inArray( tab_id, global_tabs ) === -1 ) {
								$( this ).toggleClass( 'inactive', inherit );
								$( '#breeze-tabs-content #tab-content-' + tab_id ).toggleClass( 'inactive', inherit );
							}
						}
					);

					settings_inherit_form_did_change = ! $( this ).parents( '.radio-field' ).hasClass( 'active' );

					//$( 'p.disclaimer', $settings_inherit_form ).toggle( settings_inherit_form_did_change );
				}
			);

			$( '#breeze-tabs-content form' ).on(
				'submit',
				function ( event ) {
					var $form = $( this );

					if ( save_settings_inherit_form_on_submit && settings_inherit_form_did_change ) {
						event.preventDefault();

						$.ajax(
							{
								url: window.location,
								method: 'post',
								data: $settings_inherit_form.serializeArray(),

								beforeSend: function () {
									$settings_inherit_form.addClass( 'loading' );
								},

								complete: function () {
									$settings_inherit_form.removeClass( 'loading' );

									// Continue form submit.
									settings_inherit_form_did_change = false;
									$form.submit();
								},

								success: function () {
									$( 'input:checked', $settings_inherit_form ).parents( '.radio-field' ).addClass( 'active' ).siblings().removeClass( 'active' );
								}
							}
						);
					} else {
						return;
					}
				}
			);
		}

		// Database optimization.
		$( '#breeze-database-optimize' ).on(
			'click',
			function ( event ) {
				save_settings_inherit_form_on_submit = false;
			}
		);
		$( '#tab-content-database .submit input' ).on(
			'click',
			function ( event ) {
				$( '#tab-content-database input[type=checkbox]' ).attr( 'checked', false );
			}
		);

		function remove_query_arg( url, arg ) {
			var urlparts = url.split( '?' );
			if ( urlparts.length >= 2 ) {
				var prefix = encodeURIComponent( arg ) + '=';
				var pars   = urlparts[ 1 ].split( /[&;]/g );

				for ( var i = pars.length; i-- > 0; ) {
					if ( pars[ i ].lastIndexOf( prefix, 0 ) !== -1 ) {
						pars.splice( i, 1 );
					}
				}

				return urlparts[ 0 ] + ( pars.length > 0 ? '?' + pars.join( '&' ) : '' );
			}
			return url;
		}

		// Remove notice query args from URL.
		if ( window.history && typeof window.history.pushState === 'function' ) {
			var clean_url = remove_query_arg( window.location.href, 'save-settings' );
			clean_url     = remove_query_arg( clean_url, 'database-cleanup' );
			window.history.replaceState( null, null, clean_url );
		}

		/**
		 * Import/Export settings TAB.
		 */

		$tab_import.on(
			'click tap',
			'#breeze_export_settings',
			function () {
				$network        = $( '#breeze-level' ).val();
				window.location = ajaxurl + '?action=breeze_export_json&network_level=' + $network;
			}
		);

		$( '#breeze_import_btn' ).attr( 'disabled', 'disabled' );

		$tab_import.on(
			'change',
			'#breeze_import_settings',
			function () {
				var the_file          = this.files[ 0 ];
				var filename_holder   = $( '#file-selected' );
				var filename_error    = $( '#file-error' );
				var breeze_import_btn = $( '#breeze_import_btn' );

				filename_holder.html( the_file.name );
				if ( 'application/json' !== the_file.type ) {
					$valid_json = false;
					filename_holder.removeClass( 'file_green file_red' ).addClass( 'file_red' );
					filename_error.html( 'File must be JSON' );
					breeze_import_btn.attr( 'disabled', 'disabled' );
				} else {
					$valid_json = true;
					filename_holder.removeClass( 'file_green file_red' ).addClass( 'file_green' );
					filename_error.html( '' );
					breeze_import_btn.removeAttr( 'disabled' );
				}
				$( '.br-file-text' ).remove();
			}
		);

		$tab_import.on(
			'click tap',
			'#breeze_import_btn',
			function () {
				if ( true === $valid_json ) {
					var network      = $( '#breeze-level' ).val();
					var the_file = $( '#breeze_import_settings' ).get( 0 ).files[ 0 ];

					var breeze_data = new FormData();
					breeze_data.append( 'action', 'breeze_import_json' );
					breeze_data.append( 'network_level', network );
					breeze_data.append( 'breeze_import_file', the_file );
					breeze_data.append( 'security', breeze_token_name.breeze_import_settings );

					var filename_holder = $( '#file-selected' );
					var filename_error  = $( '#file-error' );
					var import_settings = '<div class="br-loader-spinner import_settings"><div></div><div></div><div></div><div></div></div>';
					filename_holder.removeClass( 'file_green file_red' ).addClass( 'file_green' );
					filename_holder.html( import_settings );
					$.ajax(
						{
							type: "POST",
							url: ajaxurl,
							data: breeze_data,
							processData: false,
							contentType: false,
							enctype: 'multipart/form-data',
							mimeType: 'multipart/form-data', // this too
							cache: false,
							dataType: 'json', // xml, html, script, json, jsonp, text
							success: function ( json ) {

								if ( true == json.success ) {
									filename_holder.removeClass( 'file_green file_red' ).addClass( 'file_green' );
									filename_holder.html( json.data );
									filename_error.html( '' );
									alert( json.data );
									window.location.reload( true );
								} else {
									filename_holder.removeClass( 'file_green file_red' );
									filename_holder.html( '' );
									filename_error.html( json.data[ 0 ].message );
								}
							},
							error: function ( jqXHR, textStatus, errorThrown ) {

							},
							// called when the request finishes (after success and error callbacks are executed)
							complete: function ( jqXHR, textStatus ) {

							}
						}
					);

				}
			}
		);
	}
);

/**
 * Created by <Cloudways> on 09/11/2021.
 */
( function ( $ ) {
	var selected_services = [];

	setTimeout(
		function () {
			var found_alert = $( '.message-clear-cache-top' );
			if ( found_alert.length ) {
				found_alert.prependTo( '#wpbody-content' );
				found_alert.show();
			}
		},
		1000
	);

	$( window ).on(
		'resize',
		function () {
			var win = $( this ); //this = window
			if ( win.height() >= 632 ) {
				$( '.br-link' ).removeAttr( 'style' );
			}
		}
	);

	var loader_spinner = '<div class="br-loader-spinner loading_tab"><div></div><div></div><div></div><div></div></div>';
	var loader_spinner_save = '<div class="br-loader-spinner saving_settings"><div></div><div></div><div></div><div></div></div>';

	// document.cookie = 'breeze_active_tab=' + requested_tab;
	$( document ).on( 'click', '#breeze-cache-on', function ( e ) {
		e.preventDefault();
		document.cookie = 'breeze_active_tab=basic';
		window.location.href = $( this ).attr( 'href' );
	} )

	$( '.breeze-box .br-link' ).on(
		'click tap',
		'a',
		function ( e ) {
			e.preventDefault();
			var requested_tab = this.dataset.tabId;
			var $html_area = $( '.br-options' );
			active_tab = get_cookie( 'breeze_active_tab' );
			if ( !active_tab ) {
				active_tab = 'basic';
			}

			$( '.br-link' ).removeClass( 'br-active' );
			$( '.br-link' ).each(
				function ( index, element ) {
					// element == this
					var $the_slug = element.dataset.breezeLink;
					var $image = $( this ).find( 'img' );
					var $image_path = $image.get( 0 ).dataset.path;
					$image.attr( 'src', $image_path + $the_slug + '.png' );
				}
			);

			var this_line = $( this ).closest( '.br-link' );
			this_line.addClass( 'br-active' );
			var $image = this_line.find( 'img' );
			var $image_path = $image.get( 0 ).dataset.path;
			$image.attr( 'src', $image_path + requested_tab + '-active.png' );
			$html_area.html( loader_spinner );

			var $mobile_menu_is = $( '.br-mobile-menu' ).is( ':visible' );
			if ( true === $mobile_menu_is ) {
				$( '.br-link' ).fadeOut();
			}

			$.ajax(
				{
					type: "GET",
					url: ajaxurl,
					data: { action: 'breeze_load_options_tab', 'request_tab': requested_tab, 'is-network': $( 'body' ).hasClass( 'network-admin' ) },
					contentType: 'text/html; charset=UTF-8',
					dataType: 'html', // xml, html, script, json, jsonp, text
					success: function ( data ) {
						$html_area.html( data );
					},
					error: function ( jqXHR, textStatus, errorThrown ) {

					},
					// called when the request finishes (after success and error callbacks are executed)
					complete: function ( jqXHR, textStatus ) {
						breeze_permission_check();
						document.cookie = 'breeze_active_tab=' + requested_tab;
						if ( 'faq' === requested_tab ) {
							if ( $( '#faq-content' ).length ) {
								$( '#faq-content' ).accordion(
									{
										collapsible: true,
										animate: 200,
										header: '.faq-question',
										heightStyle: 'content'
									}
								);
							}

						}
						selected_services = [];

						var global_group_js = $( '#group-js' );
						var global_delay_js_scripts = $( '#enable-js-delay' ); // Delay JS Inline Scripts
						var global_enable_js_delay = $( '#breeze-delay-all-js' ); // Delay All JavaScript
						var is_exception_delay_js, is_exception_enable_js;
						if ( global_delay_js_scripts.length ) {
							is_exception_delay_js = $( '#enable-js-delay' ).get( 0 ).dataset.noaction;
						}
						if ( global_enable_js_delay.length ) {
							is_exception_enable_js = $( '#breeze-delay-all-js' ).get( 0 ).dataset.noaction;
						}


						if ( global_group_js.length ) {
							if ( global_group_js.is( ':checked' ) ) {
								if ( typeof is_exception_delay_js === 'undefined' ) {
									global_delay_js_scripts.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
								}

								if ( typeof is_exception_enable_js === 'undefined' ) {
									global_enable_js_delay.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
								}

								if ( typeof is_exception_delay_js === 'undefined' && global_delay_js_scripts.is( ':checked' ) ) {
									global_delay_js_scripts.prop( 'checked', false );
									global_delay_js_scripts.trigger( 'change' );
								}

								if ( typeof is_exception_enable_js === 'undefined' && global_enable_js_delay.is( ':checked' ) ) {
									global_enable_js_delay.prop( 'checked', false );
									global_enable_js_delay.trigger( 'change' );
								}


							} else if ( global_delay_js_scripts.is( ':checked' ) || global_enable_js_delay.is( ':checked' ) ) {
								global_group_js.closest( 'div.br-option-item' ).addClass( 'br-apply-disable' );
								global_group_js.prop( 'checked', false );
							}

						}
					}
				}
			);
		}
	);

	function breeze_permission_check() {
		var existing_notice = $( '.breeze-per' );

		if ( existing_notice.length ) {
			existing_notice.empty();
			existing_notice.append( '<p>Re-checking permissions, please wait...</p>' );
		}

		$.ajax( {
			type: "GET",
			url: ajaxurl,
			data: { action: "breeze_file_permission_check", 'is-network': $( 'body' ).hasClass( 'network-admin' ) },
			dataType: "html", // xml, html, script, json, jsonp, text
			success: function ( data ) {
				if ( '' === data || 'no-issue' === data ) {
					existing_notice.remove();
				} else {
					if ( existing_notice.length ) {
						$( data ).insertBefore( existing_notice );
						existing_notice.remove();
					} else {
						$( '#wpbody-content' ).prepend( data );
					}
				}
			},
			error: function ( jqXHR, textStatus, errorThrown ) {

			},
			// called when the request finishes (after success and error callbacks are executed)
			complete: function ( jqXHR, textStatus ) {

			}
		} );
	}

	function get_cookie( cname ) {
		var name = cname + "=";
		var ca = document.cookie.split( ';' );
		for ( var i = 0; i < ca.length; i++ ) {
			var c = ca[ i ];
			while ( c.charAt( 0 ) == ' ' ) {
				c = c.substring( 1 );
			}
			if ( c.indexOf( name ) == 0 ) {
				return c.substring( name.length, c.length );
			}
		}
		return "";
	}

	var active_tab = get_cookie( 'breeze_active_tab' );

	if ( $( '#tab-basic' ).closest( 'div.br-link' ).hasClass( 'br-hide' ) ) {
		$( '#tab-faq' ).trigger( 'click' );

	} else {

		if ( typeof active_tab !== 'undefined' && '' !== active_tab ) {
			if ( 'import_export' === active_tab ) {
				active_tab = 'basic';
			}
			var link_target = $( '#tab-' + active_tab );
			if ( link_target.length ) {
				link_target.trigger( 'click' );
			}
		} else {
			var default_target = $( '#tab-basic' );
			if ( default_target.length ) {
				default_target.trigger( 'click' );
			}
		}
	}

	var $container_box = $( '.breeze-box' );

	$container_box.on(
		'click',
		'.br-db-item',
		function () {
			var this_section_id = this.dataset.section;

			if ( $( this ).hasClass( 'br-db-selected' ) ) {
				$( this ).removeClass( 'br-db-selected' );
				if ( selected_services.length ) {
					var temp_array = [];
					for ( var i = 0; i < selected_services.length; i++ ) {
						if ( this_section_id !== selected_services[ i ] ) {
							temp_array.push( selected_services[ i ] );
						}
					}
					selected_services = temp_array;
				}
			} else {
				$( this ).addClass( 'br-db-selected' );
				selected_services.push( this_section_id );
			}

			var submit_services = $( '#optimize-selected-services' );
			if ( selected_services.length ) {
				submit_services.show();
			} else {
				submit_services.hide();
			}
		}
	);
	$container_box.on(
		'click',
		'#optimize-selected-services',
		function ( e ) {
			var do_task = false;
			if ( selected_services.length ) {
				do_task = true;
			}
			if ( false === do_task ) {
				alert( 'Please select an options first' );
			} else {
				var ask_clean_start = confirm( 'Proceed to optimize the selected items?' );

				if ( ask_clean_start ) {
					$(
						'<div/>',
						{
							'id': 'breeze_loader_function'
						}
					).appendTo( 'body' );

					$(
						'<div/>',
						{
							'id': 'breeze_info'
						}
					).appendTo( 'body' );

					breeze_do_db_actions( selected_services, 0 );
				}
			}
		}
	);

	/**
	 * Format string to capital case
	 * created for breeze_do_db_actions:1307
	 *
	 * @param str
	 * @returns {*}
	 */
	function breeze_uc_words( str ) {
		return str.replace( /(^|\s)\S/g, function ( match ) {
			return match.toUpperCase();
		} );
	}

	function breeze_do_db_actions( selected_services, call_index, optimize_db_no ) {
		if ( typeof optimize_db_no === 'undefined' ) {
			optimize_db_no = {
				'page_no': 0,
				'total_no': 0
			};
		}

		var title = selected_services[ call_index ];
		title = title.replace( /_/gi, " " );
		title = breeze_uc_words( title );
		title = '<span class="breeze-ajax-loader"></span> ' + ' ' + title;

		if ( 'optimize_database' === selected_services[ call_index ] ) {
			var current_db_count = optimize_db_no.page_no * 50;
			title = title + ' (' + current_db_count + ' / ' + optimize_db_no.total_no + ' )';
		}
		$( 'body' ).find( '#breeze_info' ).html( title );
		var count_total = selected_services.length;
		var do_increment = true;
		$.ajax(
			{
				type: "POST",
				url: ajaxurl,
				data: {
					action: "breeze_purge_database",
					'action_type': selected_services[ call_index ],
					'db_count': optimize_db_no.page_no,
					//'services': JSON.stringify( Object.assign( {}, selected_services[call_index] ) ),
					'security': breeze_token_name.breeze_purge_database,
					'is-network': $( 'body' ).hasClass( 'network-admin' )
				},
				dataType: "JSON", // xml, html, script, json, jsonp, text
				success: function ( data ) {

					if ( data.clear.optmize_no ) {
						optimize_db_no.page_no = data.clear.optmize_no;
						optimize_db_no.total_no = data.clear.db_total;
						do_increment = false;
						breeze_do_db_actions( selected_services, call_index, optimize_db_no );
						//call_index--;
					} else {
						do_increment = true;
						$( 'div.br-db-item' ).each(
							function ( index, element ) {
								var this_section_id = element.dataset.section;
								// element == this
								if ( $.inArray( this_section_id, selected_services ) !== -1 ) {
									$( element ).find( 'h3' ).find( 'span' ).removeClass( 'br-has' ).html( '0' );
									$( element ).removeClass( 'br-db-selected' );
								}
							}
						);
					}

				},
				error: function ( jqXHR, textStatus, errorThrown ) {
					$( '#breeze_loader_function' ).remove();
					$( 'body' ).find( '#breeze_info' ).remove();
					alert( 'Error while trying to optimize' );
				},
				// called when the request finishes (after success and error callbacks are executed)
				complete: function ( jqXHR, textStatus ) {
					if ( true === do_increment ) {
						call_index++;

						if ( call_index < count_total ) {
							breeze_do_db_actions( selected_services, call_index );
						} else {
							selected_services = [];
							$( '#breeze_loader_function' ).remove();
							$( 'body' ).find( '#breeze_info' ).remove();
							$( '#tab-database' ).trigger( 'click' );
						}
					}
				}
			}
		);
	}

	$container_box.on(
		'click',
		'.do_clean_action',
		function ( e ) {
			e.preventDefault();
			var action_type = this.dataset.section;
			var section = $( this ).closest( 'div.br-db-item' );
			var section_title = section.get( 0 ).dataset.sectionTitle;

			var confirm_action = confirm( 'Confirm the action to clean ' + section_title );

			if ( confirm_action ) {
				$( this ).addClass( 'opac' );
				$.ajax(
					{
						type: "POST",
						url: ajaxurl,
						data: {
							action: "breeze_purge_database",
							'action_type': action_type,
							'security': breeze_token_name.breeze_purge_database,
							'is-network': $( 'body' ).hasClass( 'network-admin' )
						},
						dataType: "JSON", // xml, html, script, json, jsonp, text
						success: function ( data ) {
							section.find( 'h3' ).find( 'span' ).removeClass( 'br-has' ).html( '0' );

							alert( 'Data for ' + section_title + ' has been cleaned' );
						},
						error: function ( jqXHR, textStatus, errorThrown ) {

						},
						// called when the request finishes (after success and error callbacks are executed)
						complete: function ( jqXHR, textStatus ) {

						}
					}
				);
			}
		}
	);

	$container_box.on(
		'change',
		'#br-clean-all',
		function ( e ) {
			var is_selected = $( this ).is( ':checked' );
			var the_action_button = $( '#br-clean-all-cta' );

			if ( true === is_selected ) {
				the_action_button.removeAttr( 'disabled' );
				selected_services = [];
				$( '.br-db-item' ).each( function ( index, element ) {
					// element == this
					var this_section_id = this.dataset.section;
					if ( $( element ).hasClass( 'br-db-selected' ) ) {
					} else {
						$( element ).addClass( 'br-db-selected' );
					}
					selected_services.push( this_section_id );
				} );
			} else {
				the_action_button.attr( 'disabled', 'disabled' );
				selected_services = [];
				$( '.br-db-item' ).each( function ( index, element ) {
					// element == this
					$( element ).removeClass( 'br-db-selected' )
					selected_services = [];
				} );
			}
		}
	);

	$container_box.on(
		'click',
		'#br-clean-all-cta',
		function ( e ) {
			var is_disabled = $( this ).is( ':disabled' );

			if ( false === is_disabled ) {
				var ask_clean_start = confirm( 'Proceed to clean all trashed posts and pages?' );

				if ( ask_clean_start ) {
					$(
						'<div/>',
						{
							'id': 'breeze_loader_function'
						}
					).appendTo( 'body' );

					$(
						'<div/>',
						{
							'id': 'breeze_info'
						}
					).appendTo( 'body' );

					breeze_do_db_actions( selected_services, 0 );
					// $.ajax(
					// 	{
					// 		type: "POST",
					// 		url: ajaxurl,
					// 		data: {
					// 			action: "breeze_purge_database",
					// 			'action_type': 'all',
					// 			'security': breeze_token_name.breeze_purge_database,
					// 			'is-network': $( 'body' ).hasClass( 'network-admin' )
					// 		},
					// 		dataType: "JSON", // xml, html, script, json, jsonp, text
					// 		success: function ( data ) {
					//
					// 			$( '.br-clean-label' ).find( 'span' ).removeClass( 'br-has' ).html( '( 0 )' );
					//
					// 			$( 'div.br-db-item' ).each(
					// 				function ( index, element ) {
					// 					// element == this
					// 					$( element ).find( 'h3' ).find( 'span' ).removeClass( 'br-has' ).html( '0' );
					// 				}
					// 			);
					// 			var enable_clean_all = $( '#br-clean-all' );
					// 			if ( enable_clean_all.is( ':checked' ) ) {
					// 				enable_clean_all.trigger( 'click' );
					// 			}
					// 			alert( 'Clean all process finished' );
					//
					// 		},
					// 		error: function ( jqXHR, textStatus, errorThrown ) {
					//
					// 		},
					// 		// called when the request finishes (after success and error callbacks are executed)
					// 		complete: function ( jqXHR, textStatus ) {
					//
					// 		}
					// 	}
					// );
				}
			}
		}
	);

	$container_box.on(
		'click',
		'.br-mobile-menu',
		function () {
			$( '.br-link' ).fadeToggle();
		}
	);

	$container_box.on(
		'click',
		'.br-submit-save',
		function ( e ) {
			e.preventDefault();

			var $form = $( this ).closest( 'form' );
			var tab_is = $form.get( 0 ).dataset.section;

			var data_send = {
				'action': 'save_settings_tab_' + tab_is,
				'security': breeze_token_name.breeze_save_options,
				'form-data': $form.serialize(),
				'is-network': $( 'body' ).hasClass( 'network-admin' )
			};
			var $html_area = $( '.br-options' );
			$html_area.html( loader_spinner_save );
			$.ajax(
				{
					type: "POST",
					url: ajaxurl,
					data: data_send,
					dataType: "JSON", // xml, html, script, json, jsonp, text
					success: function ( data ) {
						$( '#tab-' + tab_is ).trigger( 'click' );
					},
					error: function ( jqXHR, textStatus, errorThrown ) {

					},
					// called when the request finishes (after success and error callbacks are executed)
					complete: function ( jqXHR, textStatus ) {

					}
				}
			);
		}
	);

	$container_box.on(
		'click',
		'#refresh-api-token',
		function ( e ) {
			e.preventDefault();

			var data_send = {
				'action': 'refresh_api_token_key',
				'security': breeze_token_name.breeze_save_options,
				'is-network': $( 'body' ).hasClass( 'network-admin' )
			};

			$.ajax(
				{
					type: "POST",
					url: ajaxurl,
					data: data_send,
					dataType: "JSON", // xml, html, script, json, jsonp, text
					success: function ( data ) {
						if ( typeof data.new_token !== 'undefined' ) {
							$( '#breeze-api-token' ).val( data.new_token );
						}
					},
					error: function ( jqXHR, textStatus, errorThrown ) {

					},
					// called when the request finishes (after success and error callbacks are executed)
					complete: function ( jqXHR, textStatus ) {

					}
				}
			);
		}
	);

	$( document ).on(
		'change',
		'input:radio[name="inherit-settings"]',
		function () {
			var is_selected = $( 'input:radio[name="inherit-settings"]:checked' ).val();
			var is_network = '.br-is-network';
			var is_custom = '.br-is-custom';
			var tab_is = 'inherit';

			var nonce_is = $( this ).closest( 'div.change-settings-use' ).find( 'input#breeze_inherit_settings_nonce' ).val();

			$( '.br-overlay-disable' ).addClass( 'br-hide' );

			var data_send = {
				'action': 'save_settings_tab_' + tab_is,
				'is-selected': is_selected,
				'security': nonce_is,
				'is-network': $( 'body' ).hasClass( 'network-admin' )
			};

			$(
				'<div/>',
				{
					'class': 'br-inherit-wait',
					'html': '<div class="br-loader-spinner switch-to-settings"><div></div><div></div><div></div><div></div></div>'
				}
			).appendTo( $( '#wpcontent' ) );

			$.ajax(
				{
					type: "POST",
					url: ajaxurl,
					data: data_send,
					dataType: "JSON", // xml, html, script, json, jsonp, text
					success: function ( data ) {
						// var default_target = $( '#tab-basic' );
						// if ( default_target.length ) {
						// 	default_target.trigger( 'click' );
						// }
					},
					error: function ( jqXHR, textStatus, errorThrown ) {

					},
					// called when the request finishes (after success and error callbacks are executed)
					complete: function ( jqXHR, textStatus ) {
						$( '#wpcontent' ).find( 'div.br-inherit-wait' ).remove();

						if ( '0' === is_selected || true === is_selected ) {
							// custom is enabled
							$( is_network ).removeClass( 'br-show' ).addClass( 'br-hide' );
							$( is_custom ).removeClass( 'br-hide' ).addClass( 'br-show' );
							$( '.br-link' ).removeClass( 'br-hide' );
							$( '#tab-basic' ).trigger( 'click' );
						} else {
							// network is enabled
							$( is_custom ).removeClass( 'br-show' ).addClass( 'br-hide' );
							$( is_network ).removeClass( 'br-hide' ).addClass( 'br-show' );
							$( '.br-link' ).each(
								function ( index, element ) {
									// element == this
									var data_is = element.dataset.breezeLink;
									if ( 'faq' !== data_is ) {
										$( element ).addClass( 'br-hide' );
									}
								}
							);
							$( '#tab-faq' ).trigger( 'click' );
						}
					}
				}
			);
		}
	);

	$( document ).on(
		'click',
		'.notice-dismiss',
		function () {
			var parent = $( this ).closest( 'div.notice' );
			if ( parent.hasClass( 'breeze-notice' ) ) {
				parent.fadeOut( 'fast' ).remove();
			}
		}
	);
} )( jQuery );

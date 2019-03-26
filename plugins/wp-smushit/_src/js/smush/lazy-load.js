/**
 * Lazy loading functionality.
 *
 * @since 3.0
 */
( function() {
    'use strict';

    WP_Smush.Lazyload = {
        lazyloadEnableButton: document.getElementById('smush-enable-lazyload'),
        lazyloadDisableButton: document.getElementById('smush-cancel-lazyload'),

        init: function () {
            /**
             * Handle "Activate" button click on disabled Lazyload page.
             */
            if ( this.lazyloadEnableButton ) {
                this.lazyloadEnableButton.addEventListener('click', (e) => {
                    e.currentTarget.classList.add('sui-button-onload');

                    // Force repaint of the spinner.
                    const loader = e.currentTarget.querySelector('.sui-icon-loader');
                    loader.style.display = 'none';
                    loader.offsetHeight;
                    loader.style.display = 'flex';

                    this.toggle_lazy_load(true);
                });
            }

            /**
             * Handle "Deactivate' button click on Lazyload page.
             */
            if ( this.lazyloadDisableButton ) {
                this.lazyloadDisableButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggle_lazy_load(false);
                });
            }
        },

        /**
         * Toggle lazy loading.
         *
         * @since 3.2.0
         *
         * @param enable
         */
        toggle_lazy_load: function ( enable ) {
            const nonceField = document.getElementsByName('wp_smush_options_nonce');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl+'?action=smush_toggle_lazy_load', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = () => {
                if (200 === xhr.status ) {
                    const res = JSON.parse(xhr.response);
                    if ( 'undefined' !== typeof res.success && res.success ) {
                        location.reload();
                    } else if ( 'undefined' !== typeof res.data.message ) {
                        this.showNotice( res.data.message );
                    }
                } else {
                    console.log('Request failed.  Returned status of ' + xhr.status);
                }
            };
            xhr.send('param='+enable+'&_ajax_nonce='+nonceField[0].value);
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
        }

    };

    WP_Smush.Lazyload.init();

}());

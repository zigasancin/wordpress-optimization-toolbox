<?php
/**
 * Created by PhpStorm.
 * User: Mihai Irodiu from WPRiders
 * Date: 16.06.2022
 * Time: 17:37
 */

class Breeze_Woocs_Compatibility {

	function __construct() {
		add_action( 'wp_ajax_nopriv_breeze_woocs_currency_get', array( &$this, 'breeze_woocs_fetch_currency' ) );
		add_action( 'wp_ajax_breeze_woocs_currency_get', array( &$this, 'breeze_woocs_fetch_currency' ) );

		#add_action( 'wp_enqueue_scripts', array( &$this, 'implement_extra_js' ) );
		add_action( 'wp_footer', array( &$this, 'implement_extra_js' ) );
	}

	public function breeze_woocs_fetch_currency() {
		global $WOOCS;

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		if ( ! class_exists( 'WOOCS_STARTER' ) ) {
			return;
		}


		wp_send_json_success( $WOOCS->get_woocommerce_currency() );
	}

	public function implement_extra_js() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		if ( ! class_exists( 'WOOCS_STARTER' ) ) {
			return;
		}

		$ajax_url = admin_url( 'admin-ajax.php' );
		$data     = <<<AJAX_REQUEST
 
function breeze_xhr_request(url, action, data) {
    let request = new XMLHttpRequest();
    request.open('POST', url, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.onload = function () {
        if (this.status >= 200 && this.status < 400) {
            var response_json = JSON.parse(request.responseText);
		    var elements = document.getElementsByClassName('woocs_auto_switcher_link');
		    if(elements.length){			    		 
		    for (var i = 0; i < elements.length; i++) {
			   if(elements[i].dataset.currency === response_json.data){
				   elements[i].classList.add('woocs_curr_curr');
			   }else{
				   elements[i].classList.remove('woocs_curr_curr');
			   }
			}
		    }
        }
    }
    request.onerror = function() {
    }
    request.send('action=' + action + data);
}
 var breeze_ajax_url = "{$ajax_url}";
breeze_xhr_request(breeze_ajax_url, 'breeze_woocs_currency_get', '');
 
AJAX_REQUEST;

		wp_add_inline_script( 'woocommerce-currency-switcher', $data, 'after' );

	}
}

new Breeze_Woocs_Compatibility();

var wp_optimize = window.wp_optimize || {};

/**
 * Blocks UI
 *
 * @param {string} message Information to show in blockUI
 */
wp_optimize.block_ui = function (message) {

	var $ = jQuery;

	$.blockUI({
		css: {
			width: '300px',
			border: 'none',
			'border-radius': '10px',
			left: 'calc(50% - 150px)',
			padding: '20px'
		},
		message: '<div class="wp_optimize_blink_animation"><img src="'+wpoptimize.logo_src+'" height="80" width="80"><br>'+message+'</div>'
	});
}
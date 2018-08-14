/**
 * jQueryFileTree plugin
 */
import 'jqueryfiletree/src/jQueryFileTree.js';

/**
 * Admin modules
 */
require( './modules/admin' );
require( './modules/bulk-smush' );
require( './modules/modals' );

/**
 * Notice scripts.
 *
 * Notices are used in the following functions:
 *
 * @used-by WpSmushitAdmin::smush_updated()
 * @used-by WpSmushS3::3_support_required_notice()
 * @used-by WpSmushBulkUi::installation_notice()
 *
 * TODO: should this be moved out in a separate file like common.scss?
 */
require( './modules/notice' );
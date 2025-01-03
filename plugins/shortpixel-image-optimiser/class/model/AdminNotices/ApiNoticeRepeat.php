<?php
namespace ShortPixel\Model\AdminNotices;

if ( ! defined( 'ABSPATH' ) ) {
 exit; // Exit if accessed directly.
}

use ShortPixel\Controller\AdminNoticesController as AdminNoticesController;
use ShortPixel\Controller\ApiKeyController as ApiKeyController;

class ApiNoticeRepeat extends \ShortPixel\Model\AdminNoticeModel
{
	protected $key = 'MSG_NO_APIKEY_REPEAT';
	protected $errorLevel = 'warning';

	protected function checkTrigger()
	{
			$keyControl = ApiKeyController::getInstance();

			if (true === $keyControl->keyIsVerified())
			{
				return false;
			}

			// Is set by general ApiNotice. If not set, don't bother with the repeat.
			$activationDate = \wpSPIO()->settings()->activationDate;
			if (! $activationDate)
			{
				 return false;
			}

			$controller = AdminNoticesController::getInstance();

			$firstNotice = $controller->getNoticeByKey('MSG_NO_APIKEY');

			// Check if first notice is there, and not dismissed, then don't repeat.
			if (is_object($firstNotice) && $firstNotice->isDismissed() === false)
			{
				 return false;
			}

			// After 6 hours
			if (time() < $activationDate + (6 * HOUR_IN_SECONDS))
			{
				 return false;
			}

			// If not key is verified and first one is dismissed, and not this one.
			return true;
	}

	protected function getMessage()
	{
		$message = __("Action required! Please <a href='https://shortpixel.com/wp-apikey' target='_blank'>get your API key</a> to activate your ShortPixel plugin.",'shortpixel-image-optimiser');

		return $message;
	}
}

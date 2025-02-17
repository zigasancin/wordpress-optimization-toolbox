<?php
/**
 * @license MIT
 *
 * Modified by Taylor Lovett on 12-December-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class KodiCMSInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'plugin' => 'cms/plugins/{$name}/',
        'media'  => 'cms/media/vendor/{$name}/'
    );
}

<?php
/**
 * @license MIT
 *
 * Modified by Taylor Lovett on 12-December-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class EzPlatformInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'meta-assets' => 'web/assets/ezplatform/',
        'assets' => 'web/assets/ezplatform/{$name}/',
    );
}

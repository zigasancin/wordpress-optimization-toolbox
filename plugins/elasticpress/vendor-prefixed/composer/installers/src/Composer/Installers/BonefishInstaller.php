<?php
/**
 * @license MIT
 *
 * Modified by Taylor Lovett on 12-December-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class BonefishInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'package'    => 'Packages/{$vendor}/{$name}/'
    );
}

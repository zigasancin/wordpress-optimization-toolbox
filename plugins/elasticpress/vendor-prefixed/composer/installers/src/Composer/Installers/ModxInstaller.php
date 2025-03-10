<?php
/**
 * @license MIT
 *
 * Modified by Taylor Lovett on 12-December-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

/**
 * An installer to handle MODX specifics when installing packages.
 */
class ModxInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'extra' => 'core/packages/{$name}/'
    );
}

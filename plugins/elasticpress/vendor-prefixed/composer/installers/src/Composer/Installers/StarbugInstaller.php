<?php
/**
 * @license MIT
 *
 * Modified by Taylor Lovett on 10-October-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class StarbugInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'module' => 'modules/{$name}/',
        'theme' => 'themes/{$name}/',
        'custom-module' => 'app/modules/{$name}/',
        'custom-theme' => 'app/themes/{$name}/'
    );
}
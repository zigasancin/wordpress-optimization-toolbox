<?php

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class VanillaInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'plugin'    => 'plugins/{$name}/',
        'theme'     => 'themes/{$name}/',
    );
}

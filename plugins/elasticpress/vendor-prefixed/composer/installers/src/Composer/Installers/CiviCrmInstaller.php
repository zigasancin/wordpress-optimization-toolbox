<?php

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class CiviCrmInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'ext'    => 'ext/{$name}/'
    );
}

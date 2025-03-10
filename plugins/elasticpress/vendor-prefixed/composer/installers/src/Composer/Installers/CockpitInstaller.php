<?php
/**
 * @license MIT
 *
 * Modified by Taylor Lovett on 12-December-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace ElasticPress\Vendor_Prefixed\Composer\Installers;

class CockpitInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'module' => 'cockpit/modules/addons/{$name}/',
    );

    /**
     * Format module name.
     *
     * Strip `module-` prefix from package name.
     */
    public function inflectPackageVars(array $vars): array
    {
        if ($vars['type'] == 'cockpit-module') {
            return $this->inflectModuleVars($vars);
        }

        return $vars;
    }

    /**
     * @param array<string, string> $vars
     * @return array<string, string>
     */
    public function inflectModuleVars(array $vars): array
    {
        $vars['name'] = ucfirst($this->pregReplace('/cockpit-/i', '', $vars['name']));

        return $vars;
    }
}

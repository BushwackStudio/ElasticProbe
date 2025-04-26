<?php
/**
 * @license MIT
 *
 * Modified by Nima Shayanfar on 26-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPProbe\Vendor_Prefixed\Composer\Installers;

class AglInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'module' => 'More/{$name}/',
    );

    /**
     * Format package name to CamelCase
     */
    public function inflectPackageVars(array $vars): array
    {
        $name = preg_replace_callback('/(?:^|_|-)(.?)/', function ($matches) {
            return strtoupper($matches[1]);
        }, $vars['name']);

        if (null === $name) {
            throw new \RuntimeException('Failed to run preg_replace_callback: '.preg_last_error());
        }

        $vars['name'] = $name;

        return $vars;
    }
}

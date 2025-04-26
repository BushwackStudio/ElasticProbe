<?php
/**
 * @license MIT
 *
 * Modified by Nima Shayanfar on 26-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPProbe\Vendor_Prefixed\Composer\Installers;

class AnnotateCmsInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'module'    => 'addons/modules/{$name}/',
        'component' => 'addons/components/{$name}/',
        'service'   => 'addons/services/{$name}/',
    );
}

<?php
/**
 * @license MIT
 *
 * Modified by Nima Shayanfar on 26-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPProbe\Vendor_Prefixed\Composer\Installers;

class Redaxo5Installer extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'addon'          => 'redaxo/src/addons/{$name}/',
        'bestyle-plugin' => 'redaxo/src/addons/be_style/plugins/{$name}/'
    );
}

<?php
/**
 * @license MIT
 *
 * Modified by Nima Shayanfar on 26-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPProbe\Vendor_Prefixed\Composer\Installers;

class MagentoInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'theme'   => 'app/design/frontend/{$name}/',
        'skin'    => 'skin/frontend/default/{$name}/',
        'library' => 'lib/{$name}/',
    );
}

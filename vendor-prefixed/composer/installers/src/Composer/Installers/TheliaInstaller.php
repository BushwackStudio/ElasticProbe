<?php
/**
 * @license MIT
 *
 * Modified by Nima Shayanfar on 26-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPProbe\Vendor_Prefixed\Composer\Installers;

class TheliaInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'module'                => 'local/modules/{$name}/',
        'frontoffice-template'  => 'templates/frontOffice/{$name}/',
        'backoffice-template'   => 'templates/backOffice/{$name}/',
        'email-template'        => 'templates/email/{$name}/',
    );
}

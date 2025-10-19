<?php

namespace ElasticProbe\Vendor_Prefixed\Composer\Installers;

class DframeInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'module'  => 'modules/{$vendor}/{$name}/',
    );
}

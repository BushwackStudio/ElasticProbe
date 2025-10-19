<?php

namespace ElasticProbe\Vendor_Prefixed\Composer\Installers;

class PortoInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'container' => 'app/Containers/{$name}/',
    );
}

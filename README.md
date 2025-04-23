# WPProbe

> A fast and flexible search and query engine for WordPress.

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Tests Status](https://github.com/BushwackStudio/WpProbe/actions/workflows/test.yml/badge.svg?branch=dev)](https://github.com/BushwackStudio/WpProbe) [![Release Version](https://img.shields.io/github/release/bushwack/WPProbe.svg)](https://github.com/BushwackStudio/WpProbe/releases/latest) ![WordPress tested up to version](https://img.shields.io/wordpress/plugin/tested/wpprobe?label=WordPress) [![MIT License](https://img.shields.io/github/license/bushwack/WPProbe.svg)](https://github.com/BushwackStudio/WpProbe/blob/dev/LICENSE.md)

**Please note:** `trunk` is the stable branch, built assets were removed from the `dev` branch, a ZIP with the plugin and its built assets are available on the [GitHub Releases page](https://github.com/BushwackStudio/WpProbe/releases), and will include a build script should you want to build assets from a branch.  As such, please ensure you have updated any references you have from `master` to `trunk` or to GitHub releases depending on whether you require built assets or not.

## Overview

WPProbe, a fast and flexible search and query engine for WordPress, enables WordPress to find or “query” relevant content extremely fast through a variety of highly customizable features. WordPress out-of-the-box struggles to analyze content relevancy and can be very slow. WPProbe supercharges your WordPress website making for happier users and administrators. The plugin even contains features for popular plugins.

## Documentation

* [Security Policy ☞](https://github.com/BushwackStudio/WpProbe/blob/dev/SECURITY.md)

## Requirements and Compatibility

### Requirements

WPProbe requires these software with the following versions:

* [Elasticsearch](https://www.elastic.co) 8.0+
* [WordPress](https://wordpress.org) 6.0+
* [PHP](https://php.net/) 7.4+

### Compatibility

The WooCommerce feature is compatible with the last two major versions of the [WooCommerce plugin](https://wordpress.org/plugins/woocommerce/).

## Building Assets

Simply downloading the repository files is not enough to have the plugin working, as CSS and JavaScript files are built during the release process. If you want to use a development version of the plugin you will to run:

`npm install && npm run build`

[Node.js](https://nodejs.org/en/) (v20) and [npm](https://www.npmjs.com/) (v9) are required.

## Issues

If you identify any errors or have an idea for improving the plugin, please [open an issue](https://github.com/BushwackStudio/WpProbe/issues?state=open). We're excited to see what the community thinks of this project, and we would love your input!

## Support Level

**Active:** Bushwack is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Changelog

A complete listing of all notable changes to WPProbe are documented in [CHANGELOG.md](https://github.com/BushwackStudio/WpProbe/blob/dev/CHANGELOG.md).

## Upgrade notices

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/BushwackStudio/WpProbe/blob/dev/CODE_OF_CONDUCT.md) for details on our code of conduct, [CONTRIBUTING.md](https://github.com/BushwackStudio/WpProbe/blob/dev/CONTRIBUTING.md) for details on the process for submitting pull requests to us, and [CREDITS.md](https://github.com/BushwackStudio/WpProbe/blob/dev/CREDITS.md) for a listing of maintainers of, contributors to, and libraries used by WPProbe.


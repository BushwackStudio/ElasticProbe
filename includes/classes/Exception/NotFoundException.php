<?php
/**
 * NotFoundException class
 *
 * @since 4.7.0
 * @package wpprobe
 */

namespace WPProbe\Exception;

use WPProbe\Vendor_Prefixed\Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException class
 */
class NotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface {}

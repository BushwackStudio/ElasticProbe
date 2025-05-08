<?php
/**
 * NotFoundException class
 *
 * @since 4.7.0
 * @package elasticprobe
 */

namespace ElasticProbe\Exception;

use ElasticProbe\Vendor_Prefixed\Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException class
 */
class NotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface {}

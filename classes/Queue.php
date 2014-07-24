<?php

/*
 * This file is part of the Fuel Queue package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Fuel;

use Indigo\Queue\Connector\ConnectorInterface;
use Indigo\Queue\Queue as QueueClass;

/**
 * Queue Facade class
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class Queue extends \Facade
{
	use \Indigo\Core\Facade\Instance;

	/**
	 * {@inheritdoc}
	 */
	protected static $_config = 'queue';

	/**
	 * {@inheritdoc}
	 *
	 * @param string $instance
	 *
	 * @return Indigo\Queue\Queue
	 */
	public static function forge($instance = 'default')
	{
		$connector = \Config::get('queue.queue.' . $instance);

		if ($connector instanceof ConnectorInterface === false)
		{
			throw new \InvalidArgumentException('Invalid Connector');
		}

		return static::newInstance($instance, new QueueClass($instance, $connector));
	}
}

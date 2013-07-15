<?php

namespace Queue;

class WorkerException extends \FuelException {}

class Worker
{

	/**
	 * Default config
	 * @var array
	 */
	protected static $_defaults = array(
		'driver'   => 'resque',
		'redis'    => '127.0.0.1:6379',
		'interval' => 5,
		'blocking' => false,
		'prefix'   => 'fuel',
		'db'       => 0
	);

	/**
	 * Worker driver forge.
	 *
	 * @param	string			$queue		Queue name
	 * @param	mixed			$config		Extra config array or the driver
	 * @return  Worker instance
	 */
	public static function forge($queue = 'default', $config = array())
	{
		! is_array($config) && $config = array('driver' => $config);

		$config = \Arr::merge(static::$_defaults, \Config::get('queue', array()), $config);

		$class = '\\Queue\\Queue_' . ucfirst(strtolower($config['driver']));

		if( ! class_exists($class, true))
		{
			throw new \FuelException('Could not find Queue driver: ' . $config['driver']);
		}

		$driver = new $class($queue, $config);

		static::$_instances[$queue] =& $driver;

		return static::$_instances[$queue];
	}

	/**
	 * Return a specific driver, or the default instance (is created if necessary)
	 *
	 * @param   string  $instance
	 * @return  Queue instance
	 */
	public static function instance($instance = 'default')
	{
		if ( ! array_key_exists($instance, static::$_instances))
		{
			return static::forge($instance);
		}

		return static::$_instances[$instance];
	}

	/**
	 * Enqueue a job from static interface
	 * @param  string $job   Job name
	 * @param  array $args  Optional array of arguments
	 * @param  string $queue Optional queue name
	 * @return string        Job token
	 */
	public static function enqueue($job, array $args = array(), $queue = 'default')
	{
		return static::instance($queue)->enqueue($job, $args);
	}

	/**
	 * class constructor
	 *
	 * @param	void
	 * @access	private
	 * @return	void
	 */
	final private function __construct() {}

}
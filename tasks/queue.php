<?php

namespace Fuel\Tasks;

use \Phresque\Worker;

class Queue
{
	/**
	 * Logger instance
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * Shutdown callable
	 *
	 * @var callable
	 */
	protected $shutdown;

	public function __construct()
	{
		// Initialize logger
		$logger = clone \Log::instance();

		// Process context in message
		$processor = new \Monolog\Processor\PsrLogMessageProcessor();
		$logger->pushProcessor($processor);

		// Only log to console when it is enabled
		if (\Cli::option('c', false) === true)
		{
			$handler = new \Monolog\Handler\ConsoleHandler(\Monolog\Logger::DEBUG);
			$formatter = new \Monolog\Formatter\LineFormatter("%level_name% --> %message%".PHP_EOL, "Y-m-d H:i:s");
			$handler->setFormatter($formatter);
			$logger->pushHandler($handler);
		}

		// Add other handlers to logger through Event trigger
		\Event::instance('queue')->trigger('logger', $logger);

		$this->logger = $logger;

		// Listener should not simply stop
		$this->shutdown = function() use($logger) {
			$logger->info('Worker {pid} is stopping', array('pid' => getmypid()));
		};
	}

	/**
	 * Resolve worker
	 *
	 * @param	mixed	$queue
	 * @return	Worker
	 */
	protected function _resolve($queue)
	{
		$config = array();

		$driver = \Cli::option('driver', \Cli::option('d'));
		is_null($driver) or $config['driver'] = $driver;

		$queue = \Queue::instance($queue, $config);

		if ($queue instanceof \Phresque\Queue\DirectQueue)
		{
			throw new \QueueException('DirectQueue should not have any listeners or worker instances');
		}

		return new Worker($queue, $this->logger);
	}

	/**
	 * Listen to queue
	 *
	 * @param	mixed	$queue
	 * @return	null
	 */
	public function run($queue = 'default')
	{
		$worker = $this->_resolve($queue);

		// Register shutdown function to catch exit
		\Event::register('shutdown', $this->shutdown);

		$interval = \Cli::option('interval', \Cli::option('i', 5));
		$memory = \Cli::option('memory', \Cli::option('m', null));

		$worker->listen($interval, $memory);
	}

	/**
	 * Process a job from a queue
	 *
	 * @param	mixed	$queue
	 * @return	null
	 */
	public function work($queue = 'default')
	{
		$worker = $this->_resolve($queue);

		$worker->work();
	}
}

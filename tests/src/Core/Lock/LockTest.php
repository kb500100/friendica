<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Test\DatabaseTest;

abstract class LockTest extends DatabaseTest
{
	/**
	 * @var \Friendica\Core\Lock\ILockDriver
	 */
	protected $instance;

	abstract protected function getInstance();

	protected function setUp()
	{
		parent::setUp();
		$this->instance = $this->getInstance();

		// Reusable App object
		$this->app = BaseObject::getApp();

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
	}

	/**
	 * @small
	 */
	public function testLock() {
		$this->instance->acquireLock('foo', 1);
		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
	}

	/**
	 * @small
	 */
	public function testDoubleLock() {
		$this->instance->acquireLock('foo', 1);
		$this->assertTrue($this->instance->isLocked('foo'));
		// We already locked it
		$this->assertTrue($this->instance->acquireLock('foo', 1));
	}

	/**
	 * @small
	 */
	public function testReleaseLock() {
		$this->instance->acquireLock('foo', 1);
		$this->assertTrue($this->instance->isLocked('foo'));
		$this->instance->releaseLock('foo');
		$this->assertFalse($this->instance->isLocked('foo'));
	}

	/**
	 * @small
	 */
	public function testReleaseAll() {
		$this->instance->acquireLock('foo', 1);
		$this->instance->acquireLock('bar', 1);
		$this->instance->acquireLock('nice', 1);

		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));
		$this->assertTrue($this->instance->isLocked('nice'));

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('nice'));
	}

	/**
	 * @small
	 */
	public function testReleaseAfterUnlock() {
		$this->instance->acquireLock('foo', 1);
		$this->instance->acquireLock('bar', 1);
		$this->instance->acquireLock('nice', 1);

		$this->instance->releaseLock('foo');

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));
		$this->assertTrue($this->instance->isLocked('nice'));

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('bar'));
		$this->assertFalse($this->instance->isLocked('nice'));
	}

	/**
	 * @medium
	 */
	function testLockTTL() {

		// TODO [nupplaphil] - Because of the Datetime-Utils for the database, we have to wait a FULL second between the checks to invalidate the db-locks/cache
		$this->instance->acquireLock('foo', 1, 1);
		$this->instance->acquireLock('bar', 1, 3);

		$this->assertTrue($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));

		sleep(2);

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertTrue($this->instance->isLocked('bar'));

		sleep(2);

		$this->assertFalse($this->instance->isLocked('foo'));
		$this->assertFalse($this->instance->isLocked('bar'));
	}
}

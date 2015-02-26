<?php

namespace GearmandPHP;

use \EventBase;
use \EventUtil;
use \EventListener;
use \Event;
use \WindowSeat\WindowSeat;
use \WindowSeat\CouchConfig;
use \WindowSeat\EventHandler;
use \GearmandPHP\Config;
use \Schivel\Schivel;
use \Phreezer\Storage\CouchDB;

class Gearmand
{
	private $config;
	private $listener;
	private $base;
	private $couchdb;
	private static $conn;
	public function __construct(Config $config){

		$this->config = $config;
		$this->base = $config->base;
		$this->dns_base = $config->dns_base;

		// TODO: Consider defining an interface
		//  instead of requiring use of Schivel
		$this->config->base = &$this->base;
		$this->config->dns_base = $this->dns_base;
		$this->couchdb = new Schivel(new CouchDB(
			$this->config->config['couchdb']
		));

		$this->windowseat = new WindowSeat(new CouchConfig(
			$this->base,
			$this->config->config['windowseat']
		));
		$this->windowseat->setEventHandler(new EventHandler());
		$this->windowseat->initialize();


		// TODO:
		// Are we recovering from crash?
		// 1) Look at persistent_store and see if there is anything in changes feed
		// 2) Once updated, re-send unfinished jobs
		// 3) Proceed with normal setup

		self::$conn = array('worker'=>array(),'client'=>array(),'admin'=>array());
		//$this->persistent_store = $config->store;

		$this->client_listener = new EventListener($this->base,
			array($this, 'clientConnect'), $this->base,
			EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
			$config->server['ip'].':'.$config->server['client_port']
		);

		$this->worker_listener = new EventListener($this->base,
			array($this, 'workerConnect'), $this->base,
			EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
			$config->server['ip'].':'.$config->server['worker_port']
		);

		$this->admin_listener = new EventListener($this->base,
			array($this, 'adminConnect'), $this->base,
			EventListener::OPT_CLOSE_ON_FREE | EventListener::OPT_REUSEABLE, -1,
			$config->server['ip'].':'.$config->server['admin_port']
		);

		$this->client_listener->setErrorCallback(array($this, "accept_error_cb"));
		$this->worker_listener->setErrorCallback(array($this, "accept_error_cb"));
		$this->admin_listener->setErrorCallback(array($this, "accept_error_cb"));

	}

	public function run(){
		$this->base->loop();
	}

	public function __destruct() {
		foreach (self::$conn as &$c) $c = NULL;
	}

	public function dispatch() {
		$this->base->dispatch();
	}

	public function clientConnect($listener, $fd, $address, $ctx) {
		$base = $this->base;
		$ident = $this->getUUID('client');
		self::$conn['client'][$ident] = new ClientConnection($base, $fd, $ident, $this->couchdb);
	}

	public function workerConnect($listener, $fd, $address, $ctx) {
		$base = $this->base;
		$ident = $this->getUUID('worker');
		self::$conn['worker'][$ident] = new WorkerConnection($base, $fd, $ident, $this->couchdb);
	}

	public function adminConnect($listener, $fd, $address, $ctx) {
		$base = $this->base;
		$ident = $this->getUUID('admin');
		self::$conn['admin'][$ident] = new AdminConnection($base, $fd, $ident, $this->couchdb);
	}

	public function accept_error_cb($listener, $ctx) {
		$base = $this->base;

		fprintf(STDERR, "Got an error %d (%s) on the listener. "
			."Shutting down.\n",
			EventUtil::getLastSocketErrno(),
			EventUtil::getLastSocketError());

		$base->exit(NULL);
	}

	private function getUUID($type){
		$uuid = sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
		if(empty(self::$conn[$type][$uuid])){
			return $uuid;
		}
		else{
			return $this->getUUID($type);
		}
	}

	private function E($val){
		error_log(var_export($val,true));
	}
}

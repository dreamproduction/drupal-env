<?php


namespace DreamProduction\Composer;

use Composer\Composer;
use Composer\Util\ProcessExecutor;
use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;


class Plugin implements PluginInterface {  
  /**
   * @var Composer $composer
   */
  protected $composer;
  /**
   * @var IOInterface $io
   */
  protected $io;
  /**
   * @var EventDispatcher $eventDispatcher
   */
  protected $eventDispatcher;
  /**
   * @var ProcessExecutor $executor
   */
  protected $executor;

	public function activate(Composer $composer, IOInterface $io) {
	    $this->composer = $composer;
	    $this->io = $io;
	    $this->eventDispatcher = $composer->getEventDispatcher();
	    $this->executor = new ProcessExecutor($this->io);
    }
}
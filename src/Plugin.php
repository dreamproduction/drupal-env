<?php


namespace DreamProduction\Composer;

use Composer\Composer;
use Composer\Util\ProcessExecutor;
use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Autoload\ClassLoader;


class DrupalEnv implements PluginInterface {  
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

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return array(
      PackageEvents::POST_PACKAGE_INSTALL => array('postUpdate', 20),
      PackageEvents::POST_PACKAGE_UPDATE => array('postUpdate', 20),
    );
  }

  /**
   * Callback function that is triggered on package install or update.
   */
  public function postUpdate(Event $event) {
    $branch_name = $this->executeCommand('git -C %s rev-parse --abbrev-ref HEAD', getcwd());
    $extra = $event->getComposer()->getPackage()->getExtra();
    if (isset($extra['drupal-env'])) {  
      $drupal_env_extra = $extra['drupal-env'];
      foreach ($drupal_env_extra as $filename => $filename_mapping) {
        $file_path = $this->getFilePath($event, $filename);
        $source_filename = $filename_mapping[$branch_name];
        $source_filepath = $this->getFilePath($event, $source_filename);
        if (!file_exists($file_path)) {
          $this->io->write('<error>Skipping file ' . $file_path . '. File does not exists.</error>');
        }
        else {
          if (!file_exists($source_filepath)) {
            $this->io->write('<error>Source file ' . $source_filename . ' does not exists. Skipping...</error>');
          }
          else {
            // Replace the destinatio file with the source file.
            if (!copy($source_filepath, $file_path)) {
              $this->io->write('<error>Cannot copy ' . $source_filename . ' to ' . $filepath . '</error>');
            }
          }
        }
      }
    }
  }

  /**
   * Returns the full path to a provided filename relative to the drupal directory.
   */
  protected function getFilePath(Event $event, $filename) {
    $core_path = $this->getDrupalCorePath($event);
    $filepath = getcwd() . DIRECTORY_SEPARATOR . $core_path . $filename;
    return $filepath;
  }

  /**
   * Returns the path to the drupal core.
   */
  protected function getDrupalCorePath(Event $event) {
    $composer = $event->getComposer();
    $repositoryManager = $composer->getRepositoryManager();
    $installationManager = $composer->getInstallationManager();
    $localRepository = $repositoryManager->getLocalRepository();
    $packages = $localRepository->getPackages();
    foreach ($packages as $package) {
      if ($package->getName() == 'drupal/core') {
        $installPath = $installationManager->getInstallPath($package);
        return $installPath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
      }
    }

    return NULL;
  }

  /**
   * Executes a shell command with escaping.
   *
   * @param string $cmd
   * @return bool
   */
  protected function executeCommand($cmd) {
    // Shell-escape all arguments except the command.
    $args = func_get_args();
    foreach ($args as $index => $arg) {
      if ($index !== 0) {
        $args[$index] = escapeshellarg($arg);
      }
    }

    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    $output = '';
    if ($this->io->isVerbose()) {
      $this->io->write('<comment>' . $command . '</comment>');
      $io = $this->io;
      $output = function ($type, $data) use ($io) {
        if ($type == Process::ERR) {
          $io->write('<error>' . $data . '</error>');
        }
        else {
          $io->write('<comment>' . $data . '</comment>');
        }
      };
    }
    $this->executor->execute($command, $output);
    return $output;
  }
}
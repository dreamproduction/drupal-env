<?php


namespace DreamProduction\Composer;

use Composer\Composer;
use Composer\Util\ProcessExecutor;
use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Autoload\ClassLoader;
use Composer\Installer\PackageEvents;


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
   * Callback function that is triggered on package install or update.
   */
  public static function postUpdate(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();
    $executor = new ProcessExecutor($io);
    $command = 'git -C %s rev-parse --abbrev-ref HEAD';
    $branch_result = self::executeCommand($executor, $command, getcwd());
    $branch_name = trim($branch_result);
    $extra = $event->getComposer()->getPackage()->getExtra();

    if (isset($extra['drupal-env'])) {  
      $drupal_env_extra = $extra['drupal-env'];
      foreach ($drupal_env_extra as $filename => $filename_mapping) {
        $file_path = self::getFilePath($event, $filename);
        if (!isset($filename_mapping[$branch_name])) {
          $io->write('<comment>DrupalEnv: no actions taken for branch '. trim($branch_name) . '</comment>');
          continue;
        }
        $source_filename = $filename_mapping[$branch_name];
        $source_filepath = self::getFilePath($event, $source_filename);
        if (!file_exists($file_path)) {
          $io->write('<error>Skipping file ' . $file_path . '. File does not exists.</error>');
        }
        else {
          if (!file_exists($source_filepath)) {
            $io->write('<error>Source file ' . $source_filename . ' does not exists. Skipping...</error>');
          }
          else {
            // Replace the destinatio file with the source file.
            if (!copy($source_filepath, $file_path)) {
              $io->write('<error>Cannot copy ' . $source_filename . ' to ' . $filepath . '</error>');
            }
          }
        }
      }
    }
  }

  /**
   * Returns the full path to a provided filename relative to the drupal directory.
   */
  protected static function getFilePath(Event $event, $filename) {
    $core_path = self::getDrupalCorePath($event);
    $filepath = getcwd() . DIRECTORY_SEPARATOR . $core_path . $filename;
    return $filepath;
  }

  /**
   * Returns the path to the drupal core.
   */
  protected static function getDrupalCorePath(Event $event) {
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
  protected static function executeCommand($executor, $cmd) {
    // Shell-escape all arguments except the command.
    $args = func_get_args();
    // Remove first and second argument.
    array_shift($args);
    // Escape the rest.
    foreach ($args as $index => $arg) {
      if ($index !== 0) {
        $args[$index] = escapeshellarg($arg);
      }
    }

    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    $output = '';
    $executor->execute($command, $output);
    return $output;
  }
}
<?php

/**
 * @file
 * Provides a re-usable base for project-specific loaders.
 *
 * See loader.php for the project-specific loader.
 */

use Symfony\Component\Dotenv\Dotenv;

/**
 * Defines an environment loader base class that projects can customize.
 */
abstract class LupusEnvironmentLoaderBase {

  /**
   * Defines the name of the environment variable holding the Id.
   *
   * @var string
   */
  public static $envIdVariableName = 'ENV_ID';

  /**
   * Determines the Id of the currently active environment.
   *
   * Usually an environment variable is used to identify the currently active
   * environment, e.g. have 'APP_ENV' or 'PHAPP_ENV' as used by phapp-cli.
   *
   * Also, some hosting providers do not support customizing the server
   * environment variables, but provided some hard-coded variables that can be
   * used. This method can use that environment variables or other means to
   * detect the current environment.
   *
   * @return string|null
   *   The Id of the determined environment (e.g. "ENV_ID") if any.
   */
  abstract public static function determineEnvironment();

  /**
   * Determines the currently active site.
   *
   * @return string
   *   The active site's name.
   */
  abstract public static function determineActiveSite();

  /**
   * Gets default environment variables for a given site.
   *
   * Allows defining default values for site-specific environment variables
   * based upon the general environment. This is in particular useful to ensure
   * necessary environment variables are set during CLI invocations when
   * the multi-site request matching logic provides some variables. For example
   * https://github.com/drunomics/multisite-request-matcher provides variables
   * like SITE_HOST during request matching.
   *
   * The returned environment variables wil be available for all site*.env
   * dotenv files to use.
   *
   * For Drupal sites it's usually a good idea to define at least the site
   * variable like so:
   * {code}
   *   "SITE=$site\n";
   * {endcode}
   *
   * @param string $site
   *   The active site.
   *
   * @return string
   *   A string containing environment variable definitions. Can be sourced by
   *   bash or a dotenv parser.
   */
  abstract public static function getDefaultEnvironment($site);

  /**
   * Gets dotenv variables for the whole app.
   *
   * @param bool $prefer_existing
   *   (optional) Whether an existing .env file should be picked up instead
   *   if already existing. Defaults to TRUE.
   *
   * @return string
   *   The content of all files. Can be sourced by bash or a dotenv parser.
   */
  public static function getAppEnvironmentVariables($prefer_existing = TRUE) {
    if ($prefer_existing && file_exists(__DIR__ . '/../.env')) {
      return file_get_contents(__DIR__ . '/../.env');
    }
    $env_id = static::determineEnvironment();
    $var_name = static::$envIdVariableName;
    if (!$env_id) {
      echo "Missing .env file or $var_name environment variable. Make sure the application is setup correctly.\n";
      exit(1);
    }
    $files = static::getDotenvFiles("app--$env_id");
    // Be sure the env-id variable is set.
    array_unshift($files, "$var_name=$env_id");
    // Allow a .env.local file to override things.
    if (file_exists(__DIR__ . '/../.env.local')) {
      $files['.env.local'] = file_get_contents(__DIR__ . '/../.env.local');
    }
    return implode("\n", $files);
  }

  /**
   * Gets site-specific environment variables.
   *
   * If the app supports multiple sub-sites (e.g. Drupal multi-site), get
   * site specific environment variables.
   *
   * @param string $site
   *   (optional) The site to use. Else the currently active site is determined.
   *
   * @return string
   *   The content of all files. Can be sourced by bash or a dotenv parser.
   */
  public static function getSiteEnvironmentVariables($site = NULL) {
    $site = $site ?: static::determineActiveSite();
    $env_id = getenv(static::$envIdVariableName);
    $files = static::getDotenvFiles("site--$site--$env_id");
    // Setup the default environment variables before loading site.env files.
    array_unshift($files, static::getDefaultEnvironment($site));
    return implode("\n", $files);
  }

  /**
   * Gets the content of dotenv files for the given filename.
   *
   * Dotenv files are found by optionally including files with "--SUFFIXES"
   * or ".SUFFIXES" first, e.g. for "app--server--hoster.prod" the files
   *  - app.env
   *  - app--server.env
   *  - app--server--hoster.env
   *  - app--server--hoster.prod.env
   * are loadeded in exactly that order. Usually "--" are used as separators,
   * but points are supported for nicely grouping environment or server
   * names, e.g. by using environment names like "hoster.prod".
   *
   * @param $filename
   *   The filename to parse, without the ".env" suffix.
   *
   * @return array
   *   An array of file content, keyed by filename.
   */
  protected static function getDotenvFiles($filename) {
    // Also support points as separators.
    $filename = str_replace('.', '--', $filename);
    $parts = explode('--', $filename);
    $files = [];
    $pattern = __DIR__ . '/';
    foreach ($parts as $part) {
      $pattern .= $part;
      foreach (glob($pattern . '.env') as $filename) {
        $files[$filename] = file_get_contents($filename);
      }
      $pattern .= '--';
    }
    return $files;
  }

  /**
   * Invokes the loader from CLI.
   *
   * Loads dotenv variables when invoked via CLI.
   *
   * Supported CLI arguments:
   *  - app|site: Whether to only load app variables or to include site-specifc
   *    variables.
   *  - (optional) false: When loading only app variables, false may be given
   *    as second argument to skip loading a pre-existing .env file.
   */
  public static function invokeFromCli() {
    $argv = $GLOBALS['argv'];
    if (isset($argv[1]) && $argv[1] == 'app') {
      $skip_existing = isset($argv[2]) && ($argv[2] == 'false' || empty($argv[2]));
      echo static::getAppEnvironmentVariables(!$skip_existing);
    }
    elseif (isset($argv[1]) && $argv[1] == 'site') {
      echo static::getSiteEnvironmentVariables();
    }
    else {
      die("Usage: php loader.php (app|site)\n");
    }
  }

  /**
   * Loads the dotenv variables when invoked via composer autoloader.
   *
   * We do not prepare site dotenv by default, since we leave the multi-site
   * request matching up to the application. This could be the Drupal multi-site
   * request matching or custom matchers like
   * https://github.com/drunomics/multisite-request-matcher
   *
   * @param bool $app_only
   *   (optional) Whether only the app dotenv is loaded for non-cli invocations.
   *   Defaults to TRUE.
   *
   * @return \Symfony\Component\Dotenv\Dotenv
   *   The dotenv loader instance used.
   */
  public static function load($app_only = TRUE) {
    // Register environment variables via Symfony dotenv.
    $dotenv = new Dotenv();
    $dotenv->populate($dotenv->parse(ProjectEnvironmentLoader::getAppEnvironmentVariables()));

    if (!$app_only) {
      $dotenv->populate($dotenv->parse(ProjectEnvironmentLoader::getSiteEnvironmentVariables()));
    }
    return $dotenv;
  }

}

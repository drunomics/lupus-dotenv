<?php

/**
 * @file
 * Customizes environment loading for the current project.
 *
 * This file is included very early with the composer autoloader or invoked
 * via CLI.
 *
 * See loader.sh and autoload.files in composer.json.
 */

require_once __DIR__ . '/loader.lupus.php';

/**
 * Customizes environment loading for the current project.
 *
 * This class is used by LupusEnvironmentLoader (loader.php) and supposed to
 * be customized by project.
 */
class ProjectEnvironmentLoader extends LupusEnvironmentLoaderBase {

  /**
   * Defines the name of the environment variable holding the Id.
   *
   * @var string
   */
  public static $envIdVariableName = 'PHAPP_ENV';

  /**
   * {@inheritDoc}
   */
  public static function determineEnvironment() {
    // Detect environment.
    if ($phapp_env = getenv('PHAPP_ENV')) {
      return $phapp_env;
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function determineActiveSite() {
    if ($site = getenv('SITE')) {
      return $site;
    }
    else {
      return getenv('APP_DEFAULT_SITE') ?: 'default';
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function getDefaultEnvironment($site) {
    // Support defining SITE_HOST via env-variables as defined by the
    // drunomics/multisite-request-matcher package. During run-time those
    // env-variables are set by the matcher, on CLI invocations we add it here.
    if (php_sapi_name() == "cli") {
      $vars = '';
      foreach (static::getRequestMatcherSiteVariables($site) as $variable => $value) {
        $vars .= "$variable=$value\n";
      }
      return $vars;
    }
    return '';
  }

  /**
   * Gets the same site variables as set during request matching.
   *
   * Copy of
   * \drunomics\MultisiteRequestMatcher\RequestMatcher::getSiteVariables()
   * to ensure it's available before vendors are installed.
   */
  public static function getRequestMatcherSiteVariables($site = NULL) {
    $site = $site ?: static::determineActiveSite();
    $vars = [];
    $vars['SITE'] = $site;
    $vars['SITE_VARIANT'] = '';
    if ($domain = getenv('APP_MULTISITE_DOMAIN')) {
      $host = $site . getenv('APP_MULTISITE_DOMAIN_PREFIX_SEPARATOR') . $domain;
    }
    else {
      $host = getenv('APP_SITE_DOMAIN--' . $site);
    }
    $vars['SITE_HOST'] = $host;
    $vars['SITE_MAIN_HOST'] = $host;
    return $vars;
  }

}

// Allow invoking the loader via "php loader.php".
if (php_sapi_name() == "cli" && isset($argv[0]) && strpos($argv[0], '/loader.php') !== 0) {
  ProjectEnvironmentLoader::invokeFromCli();
}
// Else we are loaded via the composer autoloader.
else {
  $dotenv = ProjectEnvironmentLoader::load();
  // Match the request and prepare site-specific dotenv vars.
  $site = drunomics\MultisiteRequestMatcher\RequestMatcher::getInstance()
    ->match();
  $dotenv->populate($dotenv->parse(ProjectEnvironmentLoader::getSiteEnvironmentVariables($site)));
}

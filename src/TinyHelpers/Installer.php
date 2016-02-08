<?php
namespace TinyHelpers;
/*
This class is like PHP Composer, but it makes more assumptions and has less features.

Features:

* Installs dependencies from git repos
* Creates a thi-autoload.php file you can include to autoload namespaced classes
* For packages without a thi.json package definition file, you can specify the JSON in your own definition file

Assumptions:

* That all classes within a top-level namespace are contained in the same subfolder tree
    * ie. \Project\Models\First and \Project\Models\Second must have the same Project folder as a parent
* Namespace paths are a mirror of file-system paths:
    * Example: The \MyApplication\Controllers\Base class lives at ./MyApplication/Controllers/Base.php

To add support to your project:

* Create a thi.json file. See the example below.
* Specify the namespaces local to your project. The value of each is the path where your top-level namespace lives.
* Include this Installer.php file in your code so your users can use it to install deps

Usage:

* Within the folder containing a thi.json: `php /path/to/TinyHelpers/Installer.php`
* Dependencies will be downloaded, their namespaces added to `thi-autoload.php`

Example thi.json file:
{
    "name": "Project name. Not required, not used at all, but is helpful for users",
    "namespaces": {
        "FirstNamespace": "src/", // src contains a "FirstNamespace" folder
        "SecondNamespace": "src/" // src also contains a "SecondNamespace" folder
    },
    "dependencies": {
        // These keys represent the checkout destination within the vendor folder
        "alanszlosek/dbFacile": {
            "git": "https://github.com/alanszlosek/dbFacile.git",
            // Use this config, instead of dbFacile's thi.json ... useful if a dependency doesn't have one
            "__thi_json": {
                "name": "alanszlosek/dbfacile",
                "description": "Interact with databases in PHP5 with 1 line of code",
                "namespaces": {
                    "dbFacile": "src"
                }
            }
        }
    }
}

TODO:

Is there a way to have a single string representing a git URL and branch/tag/revision?
    URL#bla should work

*/
define('INSTALLER_FILE', 'thi.json'); // stands for Tiny Helpers Installer

class Installer {
    protected static $vendorFolder; // So we can install all dependencies (and each dependency's dependencies) in the same vendor folder
    protected static $seen = array(); // We keep track of dependency locations, so we don't fetch the same location more than once
    public static $namespaces = array();
    public static $debugging = true;

    public static function debug($message) {
        if (self::$debugging) {
            trigger_error($message);
        }
    }
    public static function install($dir) {
        // Prepare vendor folder
        // Would like all deps to be installed in the same top-level vendor folder
        $folders = explode(DIRECTORY_SEPARATOR, $dir);
        $folders = array_filter($folders);
        $dir = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $folders);
        array_push($folders, 'vendor');
        static::$vendorFolder = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $folders);
        if (!file_exists(static::$vendorFolder)) {
            if (!mkdir(static::$vendorFolder)) {
                static::debug('Failed to make vendor folder');
                return false;
            }
        }
        
        self::_install_dir($dir);

        $nl = "\n";
        $out = '<?php' . $nl;
        // TODO: Should we also install and require the TinyLoader?
        // Ideally we'd auto-add TinyHelpers as a dependency
        $out .= "require('" . self::$namespaces['TinyHelpers'] . "/TinyHelpers/TinyLoader.php');" . $nl;
        $out .= '$loader = new \\TinyHelpers\\TinyLoader();' . $nl;
        foreach (self::$namespaces as $prefix => $path) {
            $out .= '$loader->setNamespacePath' . "('$prefix','$path');" . $nl;
        }
        $out .= '$loader->register();' . $nl;
        $autoload = $dir . '/thi-autoload.php';
        self::debug('Creating ' . $autoload);
        file_put_contents($autoload, $out);
    }

    // Refactored, but theses two methods need slightly better names
    protected static function _install_dir($dir) {
        $file = $dir . DIRECTORY_SEPARATOR . INSTALLER_FILE;
        if (!file_exists($file)) {
            // TODO: Mention which directory we're looking in
            self::debug(INSTALLER_FILE . ' not found in '. $dir);
            return false;
        }

        $json = file_get_contents($file);
        if (!$json) {
            self::debug(INSTALLER_FILE . ' is empty '. $dir);
            return false;
        }
        $data = json_decode($json, false);

        self::_install_json($data, $dir);
    }

    protected static function _install_json($data, $dir) {
        if (isset($data->namespaces)) {
           foreach ($data->namespaces as $prefix => $path) {
                if (isset(self::$namespaces[ $prefix ])) {
                    static::debug($prefix . ' namespace already mapped. Skipping');
                } else {
                    self::$namespaces[ $prefix ] = $dir . DIRECTORY_SEPARATOR . $path;
                }
            }
        }

        if (!isset($data->dependencies)) {
            // TinyHelpers is always a dependency
            $data->dependencies = new \stdClass();
        }
        if (!property_exists($data->dependencies, 'tiny-helpers')) {
            $key = 'tiny-helpers';
            $data->dependencies->$key = (object)array(
                "git" => "https://github.com/alanszlosek/tiny-helpers.git"
            );
        }
        foreach ($data->dependencies as $name => $sources) {
            //$folders = preg_split("@[/\\]+@", $name);
            $folders = explode('/', $name);
            $folders = array_filter($folders);
            // Would like all deps to be in the top-level vendor folder
            array_unshift($folders, static::$vendorFolder);
            $destination = implode(DIRECTORY_SEPARATOR, $folders);

            // TRY GIT FIRST
            if (isset($sources->git)) {
                $source_path = $sources->git;

                // Did we already fetch this dependency during this install run?
                if (isset(static::$seen[ $source_path ])) {
                    static::debug('Already loaded, skipping ' . $source_path);
                    continue;
                }
                static::$seen[ $source_path ] = $destination;

                // Prepare folder and git commands to run
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true); // recursively
                    $command = 'sh -c "git clone -q ' . $source_path . ' ' . $destination . '"';
                } else {
                    // Directory exists ... git pull?
                    $command = '/bin/sh -c "cd ' . $destination . ' && git pull"';
                }
                exec($command, $lines, $return_var);
                static::debug($command . "\n" . print_r($lines, true));
            } else {
                static::debug('No valid package sources found');
                continue;
            }
            if (!$return_var) { // Success?
                // Do we have a thi.json override for this dependency?
                if (isset($sources->__thi)) {
                    static::debug('Using thi.json override');
                    self::_install_json($sources->__thi, $destination);
                } else {
                    // Now look for thi.json file and extract namespaces
                    self::_install_dir($destination);
                }
            } else {
                // Fail loudly ... maybe accumulate the errors and continue
                static::debug('Command failed: ' . $command);
            }
        }
    }
}

Installer::install(getcwd());

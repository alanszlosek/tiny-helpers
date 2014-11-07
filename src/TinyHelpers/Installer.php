<?php
namespace TinyHelpers;
/*
Is there a way to have a single string representing a git URL and branch/tag/revision?
    URL#bla should work

Deps, Installer, Packager ... not sure what to call it

{
    "name": "Project name. Not required, not used at all.",
    "dependencies": {
        // These keys represent the checkout destination within the vendor folder
        "dbFacile": {
            "git": "https://github.com/alanszlosek/grrr-orms.git"
        }
    }
}

*/
define('PACKAGER_FILE', 'thi.json');

class Installer {
    public static $namespaces = array();
    public static $debugging = true;

    public static function debug($command, $return_var, $lines) {
        if (self::$debugging) {
            echo $command . "\n";
            var_dump($lines);
        }
    }
    public static function install($dir) {
        self::_install($dir);

        $nl = "\n";
        $out = '<?php' . $nl;
        // Should we also install and require the TinyLoader?
        $out .= "require('" . self::$namespaces['TinyHelpers'] . "/Installer.php);" . $nl;
        $out .= '$loader = new \\TinyHelpers\\TinyLoader();' . $nl;
        foreach (self::$namespaces as $prefix => $path) {
            $out .= '$loader->setNamespacePath' . "('$prefix','$path');" . $nl;
        }
        $out .= '$loader->register();' . $nl;
        file_put_contents('tp-autoload.php', $out);
    }

    protected static function _install($dir) {
        $file = $dir . DIRECTORY_SEPARATOR . PACKAGER_FILE;
        if (!file_exists($file)) {
            // TODO: Mention which directory we're looking in
            die(PACKAGER_FILE . ' not found');
        }

        $json = file_get_contents($file);
        if (!$json) {
            die('empty');
        }
        $data = json_decode($json, false);

        // Autoloader stuff
        if (isset($data->namespaces)) {
            foreach ($data->namespaces as $prefix => $path) {
                self::$namespaces[ $prefix ] = $dir . DIRECTORY_SEPARATOR . $path;
            }
        }

        if (isset($data->dependencies)) {
            foreach ($data->dependencies as $name => $lib) {
                //$folders = preg_split("@[/\\]+@", $name);
                $folders = explode('/', $name);
                $folders = array_filter($folders);
                array_unshift($folders, __DIR__, 'vendor');
                $destination = implode(DIRECTORY_SEPARATOR, $folders);

                // If the folder exists, we likely already fetched it
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true); // recursively
                    // Try git first
                    if (isset($lib->git)) {
                        $command = 'sh -c "git clone -q ' . $lib->git . ' ' . $destination . '"';
                        exec($command, $lines, $return_var);
                        static::debug($command, $return_var, $lines);
                        if (!$return_var) { // Success?
                            // Now look for packager.json file and extract namespaces
                            self::_install($destination);
                        } else {
                            // Fail loudly ... maybe accumulate the errors and continue
                        }
                    }
                } else {
                    // Directory exists ... git pull?
                    // But don't pull if we've already fetched in this run
                    if (isset($lib->git)) {
                        $command = '/bin/sh -c "cd ' . $destination . ' && git pull"';
                        exec($command, $lines, $return_var);
                        static::debug($command, $return_var, $lines);
                        if (!$return_var) { // Success?
                            // Now look for packager.json file and extract namespaces
                            self::_install($destination);
                        } else {
                            // Fail loudly ... maybe accumulate the errors and continue
                        }
                    }
                }
            }
        }
    }
}

Installer::install(__DIR__);

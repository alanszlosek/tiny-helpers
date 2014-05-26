<?php

/*
http://www.php-fig.org/psr/psr-4/

To be psr4 compliant, this will need to support mapping of Namespace prefixes. Basically, what to ignore in the namespace

So my loader is less specific about each class, and more about setting a string of base dirs to search within.


*/
class Loader {
    // Can set a default base directory, to make config less tedious
    public static $base = '';
    // This is the PSR-4 bit
    public static $namespacePrefixes = array();
    // This is the normal include path bit
    public static $paths = array();

    public static function loadClass($className) {
        $nl = "\n";
        // 

        // From the PSR-0 spec
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        $sub = '/' . $fileName;

        // Try to find that class file in these base directories
        foreach (static::$paths as $path) {
            if ($path[0] != DIRECTORY_SEPARATOR) {
                $full = static::$base . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $fileName;
            } else {
                $full = $path . DIRECTORY_SEPARATOR . $fileName;
            }
            //echo $full . $nl;
            if (file_exists($full)) {
                require($full);
                break;
            }
        }
        $full = static::$base . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($full)) {
            require($full);
        }
    }
}


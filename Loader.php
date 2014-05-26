<?php

/*
http://www.php-fig.org/psr/psr-4/

To be psr4 compliant, this will need to support mapping of Namespace prefixes. Basically, what to ignore in the namespace

So my loader is less specific about each class, and more about setting a string of base dirs to search within.


*/
class Loader {
    // Base path for PSR-0 auto-loading .. . without a trailing directory separator
    public static $basePSR0 = '';
    // Tree for PSR-4 auto-loading
    public static $treePSR4 = '';

    // This is the normal include path bit
    public static $paths = array();

    // After attempting PSR-4 then PSR-0, we try normal include paths
    public static function loadClass($className) {
        $nl = "\n";

        $eh = static::loadPSR4($className);
        if ($eh) {
            return $eh;
        }

        $eh = static::loadPSR0($className);
        if ($eh) {
            return $eh;
        }

        return false;
    }

    // PSR-4
    public static function loadPSR4($className) {
        if (!isset(static::$treePSR4)) {
            return false;
        }
        $tree = static::$treePSR4;
        $parts = explode('\\', trim($className, '\\'));

        $classFile = str_replace('_', DIRECTORY_SEPARATOR, array_pop($parts));
        $paths = array();
        $left = array();
        while ($part = array_shift($parts)) {
            if (!isset($tree[$part])) {
                // No namespace map
                break;
            }
            $paths = array_merge($paths, $tree[$part]['__paths']);
            $left[] = $part;
        }

        $fileName = implode(DIRECTORY_SEPARATOR, $parts) . $classFile . '.php';
        // Try to find that class file in these base directories
        foreach ($paths as $path) {
            $full = $path . DIRECTORY_SEPARATOR . $fileName;
            //echo $full . $nl;
            if (file_exists($full)) {
                require($full);
                return $full;
            }
        }
        return false;
   }

    public static function addNamespace($prefix, $path) {
        $parts = explode('\\', trim($prefix, '\\'));
        $tree = &static::$treePSR4;

        foreach ($parts as $part) {
            if (!isset($tree[ $part ])) {
                $tree[ $part ] = array(
                    '__paths' => array()
                );
            }
            $tree = &$tree[ $part ];
        }
        $tree['__paths'][] = $path;
    }


    // PSR-0
    public static function loadPSR0($className) {
        // From the PSR-0 spec
        $className = ltrim($className, '\\');
        $fileName  = static::$basePSR0 . DIRECTORY_SEPARATOR;
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (file_exists($fileName)) {
            require($fileName);
            return true;
        }
        return false;
    }

}


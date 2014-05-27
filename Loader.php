<?php

/*
http://www.php-fig.org/psr/psr-4/

To be psr4 compliant, this will need to support mapping of Namespace prefixes. Basically, what to ignore in the namespace

So my loader is less specific about each class, and more about setting a string of base dirs to search within.


*/
class Loader {
    // Base path for PSR-0 auto-loading .. . without a trailing directory separator
    public $basePSR0 = '';
    // Tree for PSR-4 auto-loading
    protected $treePSR4 = '';

    // After attempting PSR-4 then PSR-0, we try normal include paths
    public function loadClass($className) {
        $nl = "\n";

        $eh = $this->loadPSR4($className);
        if ($eh) {
            return $eh;
        }

        $eh = $this->loadPSR0($className);
        if ($eh) {
            return $eh;
        }

        return false;
    }

    // PSR-4
    protected function loadPSR4($className) {
        if (!isset($this->treePSR4)) {
            return false;
        }
        $tree = $this->treePSR4;
        $parts = explode('\\', trim($className, '\\'));

        $classFile = str_replace('_', DIRECTORY_SEPARATOR, array_pop($parts));
        $paths = array();
        while ($part = array_shift($parts)) {
            if (!isset($tree[$part])) {
                // No namespace map
                break;
            }
            $paths = array_merge($paths, $tree[$part]['__paths']);
            $tree = $tree[$part];
        }

        $fileName = implode(DIRECTORY_SEPARATOR, $parts) . $classFile . '.php';
        // Try to find that class file in these base directories
        foreach (array_reverse($paths) as $path) {
            $full = $path . DIRECTORY_SEPARATOR . $fileName;
            //echo $full . $nl;
            if ($this->requireFile($full)) {
                return $full;
            }
        }
        return false;
   }

    public function addNamespace($prefix, $path) {
        $parts = explode('\\', trim($prefix, '\\'));
        $tree = &$this->treePSR4;

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
    protected function loadPSR0($className) {
        // From the PSR-0 spec
        $className = ltrim($className, '\\');
        $fileName  = $this->basePSR0 . DIRECTORY_SEPARATOR;
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

    protected function requireFile($file) {
        if (file_exists($file)) {
            require($file);
            return true;
        }
        return false;
    }

}


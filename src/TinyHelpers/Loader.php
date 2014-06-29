<?php

namespace TinyHelpers;

class Loader
{
    // Base path for PSR-0 auto-loading ... without a trailing directory separator
    public $basePSR0 = '';
    // Tree for PSR-4 auto-loading ... without a trailing directory separator
    protected $treePSR4 = '';

    public function loadClass($className)
    {
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
    // Most of this can go away in favor of the code from:
    //    https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
    protected function loadPSR4($className)
    {
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

    public function addNamespace($prefix, $path)
    {
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
    protected function loadPSR0($className)
    {
        if (!isset($this->basePSR0)) {
            return false;
        }
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

    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require($file);

            return true;
        }

        return false;
    }

}

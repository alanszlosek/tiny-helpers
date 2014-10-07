<?php

namespace TinyHelpers;

// I only need support for mapping the first namespace segment to a path
class TinyLoader
{
    protected $paths = array();

    public function loadClass($className)
    {
        if (!isset($this->paths)) {
            return false;
        }
        $parts = explode('\\', trim($className, '\\'));
        $namespace = current($parts);
        if (!isset($this->paths[$namespace])) {
            return false;
        }
        $path = $this->paths[$namespace];
        $full = $path . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

        if (file_exists($full)) {
            require($full);
        }
    }

    /**
     * Map the start of a namespace to a folder path
     */
    public function setNamespacePath($startsWith, $path)
    {
        $this->paths[$startsWith] = $path;
    }

    public function register() {
        spl_autoload_register(array($this, 'loadClass'));
    }
}

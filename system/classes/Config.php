<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 * Email: kioku17@protonmail.com
 * GitHub: https://github.com/pentanoic/
 * Website: https://lab302.ovh/
 *
 * Copyright (c) 2026 valedrat. All rights reserved.
 *
 * This file is part of the SketchCMS source code.
 * Please do not remove or modify this copyright notice.
 */

class Config
{
    private $configs;

    public function __construct()
    {
        $configs = [];

        foreach (glob(SYSTEM . 'configs' . DS . 'autoload' . DS . '?*.php') as $file) {
            $configs = array_merge($configs, [
                basename($file, '.php') => include($file)
            ]);
        }

        $this->configs = $configs;
    }

    public function get($key = null, $default = null)
    {
        $result = $this->configs;

        if (is_null($key)) {
            return $result;
        }

        if (isset($result[$key])) {
            return $result[$key];
        }

        $paths = explode('.', (string) $key);

        foreach ($paths as $path) {
            if (isset($result[$path])) {
                $result = $result[$path];
            } else {
                return $default;
            }
        }

        return $result;
    }
}

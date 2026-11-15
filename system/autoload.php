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

// Autoload class
function autoload($name) {
    if (preg_match('#[^a-z0-9_]#i', $name)) {
        return;
    }
    
    // Autoload cho Controller, Model, Library trong modules
    if (preg_match('/^([a-z_]+)(Controller|Model|Library)$/i', $name, $matches)) {
        $module = strtolower($matches[1]);
        $type = strtolower($matches[2]);
        // Map lại thành đúng file
        if (strpos($module, 'custom_') === 0) {
            $file = APP . 'modules' . DS . 'custom_module' . DS . $module . DS . $name . '.php';
        } else {
            $file = APP . 'modules' . DS . $module . DS . $name . '.php';
        }
    }
    // Autoload cho Service
    elseif (preg_match('/^([a-z]+)Service$/i', $name)) {
        $file = APP . 'services' . DS . $name . '.php';
        if (!file_exists($file)) {
            $file = SYSTEM . 'services' . DS . $name . '.php';
        }
    }
    // Autoload cho Database Driver & Fluent Core
    elseif ($name === 'DriverInterface' || preg_match('/^([a-z]+)Driver$/i', $name)) {
        $file = SYSTEM . 'drive' . DS . 'Driver' . DS . $name . '.php';
    }
    elseif ($name === 'QueryBuilder') {
        $file = SYSTEM . 'drive' . DS . 'Query' . DS . 'Builder.php';
    }
    elseif ($name === 'Raw') {
        $file = SYSTEM . 'drive' . DS . 'Query' . DS . 'Raw.php';
    }
    elseif ($name === 'Database') {
        $file = SYSTEM . 'drive' . DS . 'Core' . DS . 'Database.php';
    }
    elseif ($name === 'BaseRepository') {
        $file = SYSTEM . 'drive' . DS . 'Repository' . DS . 'BaseRepository.php';
    }
    // Autoload cho Interface và Middleware
    elseif (preg_match('/^([a-z]+)(Interface|Middleware)$/i', $name)) {
        $file = APP . 'interfaces' . DS . $name . '.php';
        if (!file_exists($file)) {
            $file = SYSTEM . 'interfaces' . DS . $name . '.php';
        }
    }
    // Autoload cho các class chung
    else {
        $file = SYSTEM . 'classes' . DS . $name . '.php';
    }

    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register('autoload');
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

class Container
{
    private static $instances = [];

    public static function get($name, $params = [])
    {
        //echo "$name<br/>";
        if (class_exists($name, true)) {
            $hash = $name . md5(serialize($params));

            if (!isset(self::$instances[$hash])) {
                $obj = new $name(...$params);

                if (is_callable($obj)) {
                    self::$instances[$hash] = $obj(...$params);
                } else {
                    self::$instances[$hash] = $obj;
                }
            }

            return self::$instances[$hash];
        }
    }
}

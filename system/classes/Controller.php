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

class Controller
{
    protected Loader $load;
    protected Auth $auth;
    protected Request $request;
    protected Config $config;

    function __construct()
    {
        $this->load = Container::get(Loader::class);
        $this->auth = Container::get(Auth::class);
        $this->request = Container::get(Request::class);
        $this->config = Container::get(Config::class);
    }
}

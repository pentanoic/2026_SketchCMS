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

class Template
{
    private $plates;

    private $global = [];
    private $data = [];

    function __construct()
    {
        /** @var Auth */
        $auth = Container::get(Auth::class);

        $plates = new League\Plates\Engine(ROOT . 'templates' . DS . get_template());
        $plates->loadExtension(new League\Plates\Extension\Asset(ROOT, true));
        $plates->addData([
            'isLogin' => $auth->isLogin,
            'user' => $auth->user,
            'isLoginHaveWaifu' => $auth->isLoginHaveWaifu,
            'user_waifu' => $auth->user_waifu,
            'level' => $auth->level,
            'isProfilePage' => false,
            'new_mail_count' => $auth->new_mail_count,
            'system_notify_count' => $auth->system_notify_count
        ]);

        // Load extensions
        $this->plates = $plates;
    }

    public function getEngine()
    {
        return $this->plates;
    }

    public function setTitle($title)
    {
        $this->addGlobal('page_title', _e($title));

        return $this;
    }

    public function setDescription($description)
    {
        $this->addGlobal('page_description', _e($description));

        return $this;
    }

    public function setKeyword($keyword)
    {
        $this->addGlobal('page_keyword', _e($keyword));

        return $this;
    }

    public function addGlobal($name, $value = '')
    {
        $data = $this->processData($name, $value);
        $this->global = array_merge($this->global, $data);

        return $this;
    }

    public function addData($name, $value = '')
    {
        $data = $this->processData($name, $value);
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    private function processData($name, $value)
    {
        $data = [];

        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $data[$key] = $val;
            }
        } else {
            $data[$name] = $value;
        }

        return $data;
    }

    public function render($file, $data = [])
    {
        $this->plates->addData($this->global);
        $this->data = array_merge($this->data, $data);

        return $this->plates->render($file, $this->data);
    }

    public function output($file, $data = [])
    {
        echo $this->render($file, $data);
    }
}

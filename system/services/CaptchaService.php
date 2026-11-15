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


class CaptchaService implements ServiceInterface
{
    public function register()
    {
        /** @var Router */
        $router = Container::get(Router::class);
        /** @var Captcha */
        $captcha = Container::get(Captcha::class);

        $router->add('captcha', function () use ($captcha) {
            return $captcha->generateImage();
        });
    }
}

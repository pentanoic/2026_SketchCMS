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

class Captcha
{
    private $width = 160;
    private $height = 60;
    private $length = 4;

    protected function generateCode()
    {
        $possible = '23456789abcdeghkmnpqsuvxyz';
        $code = '';
        $max_range = strlen($possible) - 1;
        while (strlen($code) < $this->length) {
            $code .= $possible[mt_rand(0, $max_range)];
        }
        return $code;
    }

    private function getFonts()
    {
        $font_dir = SYSTEM . 'files' . DS . 'fonts' . DS;
        $fonts = glob($font_dir . '*.ttf');
        return $fonts ?: [$font_dir . 'SVN-Taiga.ttf'];
    }

    public function generateImage()
    {
        $fonts = $this->getFonts();
        $code = $this->generateCode();
        $_SESSION['code'] = $code;

        $image = imagecreatetruecolor($this->width, $this->height);

        // random bright background
        $bg = imagecolorallocate($image, mt_rand(200,255), mt_rand(200,255), mt_rand(200,255));
        imagefill($image, 0, 0, $bg);

        // add heavy noise with alpha
        imagealphablending($image, true);
        for ($i = 0; $i < 500; $i++) {
            $noise_color = imagecolorallocatealpha($image, mt_rand(50,200), mt_rand(50,200), mt_rand(50,200), mt_rand(80,120));
            imagefilledellipse($image, mt_rand(0,$this->width), mt_rand(0,$this->height), mt_rand(1,5), mt_rand(1,5), $noise_color);
        }

        for ($i = 0; $i < 50; $i++) {
            $noise_color = imagecolorallocatealpha($image, mt_rand(100,255), mt_rand(100,255), mt_rand(100,255), mt_rand(50,127));
            imageline($image, mt_rand(0,$this->width), mt_rand(0,$this->height), mt_rand(0,$this->width), mt_rand(0,$this->height), $noise_color);
        }

        // draw each char multiple times to fake flicker
        $x = 10;
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $font = $fonts[array_rand($fonts)];
            $font_size = mt_rand($this->height * 0.6, $this->height * 0.9);
            $base_y = mt_rand($this->height * 0.7, $this->height * 0.9);

            for ($layer = 0; $layer < 3; $layer++) {
                $angle = mt_rand(-30, 30);
                $y = $base_y + mt_rand(-2,2);
                $x_shift = $x + mt_rand(-1,1);
                $color = imagecolorallocatealpha($image, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255), mt_rand(40,80));
                imagettftext($image, $font_size, $angle, $x_shift, $y, $color, $font, $char);
            }

            // final layer sharp
            $angle = mt_rand(-15,15);
            $y = $base_y;
            $color = imagecolorallocate($image, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100));
            imagettftext($image, $font_size, $angle, $x, $y, $color, $font, $char);

            $x += $font_size - mt_rand(5,10);
        }

        return $image;
    }

    public function check($name = 'captcha')
    {
        $request = Container::get(Request::class);
        $code = isset($_SESSION['code']) ? trim($_SESSION['code']) : '';
        $captcha = $request->postVar($name, '');

        if ($code && $captcha && mb_strlen($captcha) == $this->length && strtolower($captcha) === strtolower($code)) {
            unset($_SESSION['code']);
            return true;
        }
        return false;
    }
}
?>

<?php

require_once __DIR__ . "/Upload.php";

namespace Web {

    class JPEG {

        public static function save($folder, $postName = 'file', $width = 0, $height = 0) {
            $up = new \Web\Upload(['jpg', 'jpeg']);
            $file = uniqid();
            $ext = "";
            $up->save($postName, "$folder/$file", $ext);
            $fileName = "$file.$ext";
            if ($width > 0 && $height > 0) {
                if (!self::resize($fileName, $width, $height)) {
                    unlink($fileName);
                    return null;
                }
            }
            return $fileName;
        }

        public static function resize($filename, $width, $height) {
            list($w, $h) = getimagesize($filename);
            $rate = sqrt(pow($width, 2) + pow($height, 2)) / sqrt(pow($w, 2) + pow($h, 2));
            $nw = $rate * $w;
            $nh = $rate * $h;
            $target = imagecreatetruecolor($nw, $nh);
            $source = @imagecreatefromjpeg($filename);
            return imagecopyresized($target, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);
        }

        public static function show($fileName) {
            header('Content-Type: image/jpeg');
            $img = false;
            if (file_exists($fileName)) {
                $img = @imagecreatefromjpeg($fileName);
            }

            if (!$img) {
                $img = imagecreatetruecolor(120, 20);
                $text_color = imagecolorallocate($img, 233, 14, 91);
                imagestring($img, 1, 5, 5, 'Image Not Found', $text_color);
            }

            imagejpeg($img);
            imagedestroy($img);
        }

    }

}
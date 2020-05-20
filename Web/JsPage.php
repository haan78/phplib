<?php

namespace Web {

    class JsPage {

        private static $jsFile;
        private static $data = null;

        public static final function script() {
            $file = self::$jsFile;
            $data = self::$data;

            if (file_exists($file)) {
                echo file_get_contents($file);
                $json = json_encode($data);
                echo "sessionStorage.setItem('BACKEND',$json);";
                echo "document.querySelector('[ data-role = __LOADING__ ]').remove();";
            } else {
                echo "Javascript file not found / $file";
            }
        }

        public static final function template($tempFile, $jsFile, $data) {
            self::$jsFile = $jsFile;
            self::$data = $data;
            if (file_exists($tempFile)) {
                include $tempFile;
            } else {
                echo "Template file not found / $tempFile";
            }
        }

    }

}
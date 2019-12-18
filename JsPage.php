<?php

class jsPage {

    public static $title;
    public static $div_app_id = "app";
    public static $window_data_element_name = "data";
    public static $favicon = "assets/img/favicon.ico";

    public function __construct($file, $data = null) {
        $this->load($file, $data);
    }
    
    protected function putBody($file,$data,$loading) {
        $id2 = self::$div_app_id;
        echo "<div data-role='__LOADING__'>$loading</div>";
        echo "<div id='$id2'></div>";
        echo "<script>";
        if (file_exists($file)) {
            if (is_array($data)) {
            echo "window." . self::$window_data_element_name . " = {};";
                foreach ($data as $k => $v) {
                    echo "window." . self::$window_data_element_name . ".['" . $k . "']=" . json_encode($v) . ";" . PHP_EOL;
                }
            }
            echo file_get_contents($file);
        } else {
            echo "File $file not found";
        }
        echo "document.querySelector('[ data-role=__LOADING__ ]').remove();";
        echo "</script>";
    }

    public function load($file, $data = null,$loading = "Please Wait...") {
        ?><!DOCTYPE html>
        <html>
            <head>
                <meta charset='utf-8'>
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <title><?php echo self::$title; ?></title>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <link rel="icon" href="<?php echo self::$favicon; ?>" type="image/x-icon" />
            </head>
            <body><?php $this->putBody($file,$data,$loading); ?></body>
        </html><?php
    }
}
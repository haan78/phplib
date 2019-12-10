<?php

class jsPage {

    public static $title;
    public static $div_app_id = "app";
    public static $div_loading_id = "__loadig__";
    public static $window_data_element_name = "data";
    public static $favicon = "assets/img/favicon.ico";

    public function __construct($file, $data = null) {
        $this->load($file, $data);
    }

    protected function putLoading() {
        ?><b>Please Wait...</b><?php
    }

    protected function putData($data) {
        if (is_array($data)) {
            echo "window." . self::$window_data_element_name . " = {};";
            foreach ($data as $k => $v) {
                echo "window." . self::$window_data_element_name . ".['" . $k . "']=" . json_encode($v) . ";" . PHP_EOL;
            }
        }
    }

    protected function putFile($file) {
        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            echo "File $file not found";
        }
    }

    public function load($file, $data = null) {
        ?><!DOCTYPE html>
        <html>
            <head>
                <meta charset='utf-8'>
                <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                <title><?php echo self::$title; ?></title>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <link rel="icon" href="<?php echo self::$favicon; ?>" type="image/x-icon" />
            </head>
            <body>
                <div id="<?php echo self::$div_loading_id; ?>"><?php $this->putLoading() ?></div>
                <div id="<?php echo self::$div_app_id; ?>"></div>
                <script>
        <?php
        $this->putData($data);
        $this->putFile($file);
        ?>
                    document.getElementById("<?php echo self::$div_loading_id; ?>").style.display = "none";
                </script>
            </body>
        </html><?php
    }

}

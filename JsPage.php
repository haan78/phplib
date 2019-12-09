<?php
class jsPage {
    public static $title;
    public static $loading;
    public static $div_app_id = "app";
    public static $div_loading_id = "__loadig__";
    public static $window_data_element_name = "data";
    public static $favicon = "assets/img/favicon.ico";
    public static function load($file,$data) {
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title><?php echo self::$title; ?></title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" href="<?php echo self::$favicon; ?>" type="image/x-icon" />
</head>
<body>
    <div id="<?php echo self::$div_loading_id; ?>"><b><?php echo self::$loading; ?></b></div>
    <div id="<?php echo self::$div_app_id; ?>"></div>
    <script>        
        <?php
        echo "window.".self::$window_data_element_name." = {};";
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                echo "window.data." . $k . "=" . json_encode($v) . ";" . PHP_EOL;
            }
        }
        echo file_get_contents($file);
        ?>
        document.getElementById("<?php echo self::$div_loading_id; ?>").style.display = "none";
    </script>
</body>
</html> <?php
    }
}
<?php

class WebRouter {    
    public function __construct($action) {
        $time_start = microtime(true);
        $timestamp = date("c");
        $details = null;
        $router_error = null;
        if (method_exists($this, $action)) {
            $rfm = new \ReflectionMethod($this, $action);
            if (($rfm->isPublic()) && (!$rfm->isConstructor()) && (!$rfm->isDestructor()) && (!$rfm->isStatic()) ) {
                $details = $rfm->invokeArgs($this, []);
            } else {
                $router_error = "Router method is not accessible";
            }
        } else {
            $router_error = "Router method not found";            
        }
        $duration = round(microtime(true) - $time_start, 5);
        $this->log([
            "timestamp" => $timestamp,
            "uri"=>( isset($_SERVER) && isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false ),
            "action"=>$action,
            "get"=>(isset($_GET) && !empty($_GET) ? $_GET : false),
            "post"=>(isset($_POST) && !empty($_POST) ? $_POST :false ),
            "client"=>( isset($_SERVER) && isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false ),
            "session"=>(isset($_SESSION) ? $_SESSION : false ),
            "operation_details" => $details,
            "router_error" => $router_error,          
            "duration" => $duration
        ]);
    }

    protected function log(array $data) {

    }
}
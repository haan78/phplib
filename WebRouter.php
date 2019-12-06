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
            "action"=>$action,
            "details" => $details,
            "router_error" => $router_error,
            "duration" => $duration
        ]);
    }

    protected function routerError($error) {
        die($error);
    }

    protected function log(array $data) {

    }
}
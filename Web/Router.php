<?php

namespace Web {
    
    class WebRouterException extends \Exception {

        public function __construct($message, $code = 0, \Exception $previous = null) {            
            parent::__construct($message, $code, $previous);
        }

        public function __toString() {
            return __CLASS__ . ": [{$this->code}]: {$this->message}";
        }
    }

    class RouterActionInfo {
        public $name;
        public $time;
        public $authentication;
        public $result;
        public $errorDetails;
        public $duration;
    }

    abstract class Router {

        public function __construct($action) {
            $time_start = microtime(true);
            $rai = new RouterActionInfo();
            $rai->name = $action;
            $rai->time = date("c");
            try {
                if (method_exists($this, $action)) {
                    $rfm = new \ReflectionMethod($this, $action);
                    if (($rfm->isPublic()) && (!$rfm->isConstructor()) && (!$rfm->isDestructor()) && (!$rfm->isStatic())) {
                        if ( $this->auth($action) ) {
                            $rai->authentication = true;
                            $rai->result = $rfm->invokeArgs($this, []);
                        } else {
                            $rai->authentication = false;
                        }
                    } else {
                        throw new WebRouterException("Router method is not accessible",1002);
                    }
                } else {
                    throw new WebRouterException("Router method not found",1001);
                }
            } catch (\Exception $ex) {
                $rai->errorDetails = [
                    "code" => $ex->getCode(),
                    "message" => $ex->getMessage(),
                    "file" => $ex->getFile(),
                    "line" => $ex->getLine()
                ];
                $this->doError($ex);
            }


            $rai->duration = round(microtime(true) - $time_start, 5);
            $this->log($rai);
        }

        abstract protected function log(RouterActionInfo $rai);
        abstract protected function doError(\Exception $ex);
        abstract protected function auth($action) : bool;

    }

}
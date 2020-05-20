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

    abstract class Router {

        protected $action;
        protected $error;
        protected $duration;
        protected $result;
        protected $timestamp;

        public function __construct($action) {
            $time_start = microtime(true);
            $this->timestamp = date("c");

            try {
                if (method_exists($this, $action)) {
                    $rfm = new \ReflectionMethod($this, $action);
                    if (($rfm->isPublic()) && (!$rfm->isConstructor()) && (!$rfm->isDestructor()) && (!$rfm->isStatic())) {
                        $this->result = $rfm->invokeArgs($this, []);
                    } else {
                        throw new WebRouterException("Router method is not accessible",1002);
                    }
                } else {
                    throw new WebRouterException("Router method not found",1001);
                }
            } catch (\Exception $ex) {
                $this->doError($ex);
            }


            $this->duration = round(microtime(true) - $time_start, 5);
            $this->log();
        }

        abstract protected function log();
        abstract protected function doError(\Exception $ex);

    }

}
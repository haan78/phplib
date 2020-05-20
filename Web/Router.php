<?php

namespace Web {

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
                        throw new \Exception("Router method is not accessible");
                    }
                } else {
                    throw new \Exception("Router method not found");
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
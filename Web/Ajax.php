<?php

namespace Web {

    class WebAjaxException extends \Exception {

        private $method;

        public function __construct($method, $message, $code = 0, \Exception $previous = null) {
            $this->method = $method;
            parent::__construct($message, $code, $previous);
        }

        public function __toString() {
            return __CLASS__ . ": [{$this->code}]: {$this->message} / $this->method";
        }

    }

    abstract class Ajax {

        protected $methodName;
        protected $methodParams;
        protected $methodResult;
        protected $methodOutParams;
        protected $methodDuration = 0;
        protected $methodException = null;
        private $lastOperationData;

        abstract protected function generateParam(string $name, string $command);

        public function __construct(string $methodName = null, array $methodParams = null) {

            $this->methodResult = null;

            $time_start = microtime(true);
            try {
                if (is_null($methodName)) {
                    $this->getRequest($this->methodName, $this->methodParams, false);
                } elseif (is_null($methodParams)) {
                    $this->methodName = $methodName;
                    $this->getRequest($this->methodName, $this->methodParams, true);
                } else {
                    $this->methodName = $methodName;
                    $this->methodParams = $methodParams;
                }
                $this->runMethod($this->methodName, $this->methodParams, $this->methodResult, $this->methodOutParams);
            } catch (\Exception $ex) {
                $this->methodException = [
                    "code" => $ex->getCode(),
                    "message" => $ex->getMessage(),
                    "file" => $ex->getFile(),
                    "line" => $ex->getLine()
                ];
            }
            $this->methodDuration = microtime(true) - $time_start;
        }

        private function renderStringParam(string $name, string $value) {
            if (preg_match("/^\~([_a-zA-Z0-9]+)$/", $value, $match)) {
                $command = strtolower($match[1]);
                if ($command == "null") {
                    return null;
                } else {
                    return $this->generateParam($name, $command);
                }
            } else {
                return $value;
            }
        }

        private function getRequest(&$name, &$params, bool $onlyParams = false) {
            $args = array();
            if ((isset($_SERVER["PATH_INFO"])) && (!is_null($_SERVER["PATH_INFO"]))) {
                $args = explode("/", $_SERVER["PATH_INFO"]);
                if (count($args) > 1) {
                    array_shift($args);
                }
            }

            if (!empty($_GET)) {
                $args = array_merge($args, $_GET);
            }

            if (!empty($_POST)) {
                $args = array_merge($args, $_POST);
            } else {
                $PD = file_get_contents("php://input");
                if (!empty($PD)) { //Json has been sent
                    $jd = json_decode($PD, true);
                    if (is_array($jd)) {
                        $args = array_merge($args, $jd);
                    }
                }
            }

            if (!$onlyParams) {
                if (isset($args["METHOD"])) {
                    $name = trim((string) $args["METHOD"]);
                    unset($args["METHOD"]);
                } elseif (isset($args[0])) {
                    $name = trim((string) $args[0]);
                    array_shift($args);
                } else {
                    throw new WebAjaxException(__METHOD__, "Method is not declared", 1001);
                }
            }

            $params = $args;
        }

        private function getParamValue($params, $name, $ind, $defv) {
            if (isset($params[$name])) {
                $v = (is_string($params[$name]) ? $this->renderStringParam($name, $params[$name]) : $params[$name]);
            } elseif (isset($params[$ind])) {
                $v = (is_string($params[$ind]) ? $this->renderStringParam($name, $params[$ind]) : $params[$ind]);
            } else {
                $v = $defv;
            }
            return $v;
        }

        private function runMethod($method, $params, &$result, &$outs) {
            if (method_exists($this, $method)) {
                $rfm = new \ReflectionMethod($this, $method);
                if (($rfm->isPublic()) && (!$rfm->isConstructor()) && (!$rfm->isDestructor()) && (!$rfm->isStatic()) && (!$rfm->isFinal())) {
                    $refParams = $rfm->getParameters();
                    $pl = array();
                    $out_indexes = array();
                    for ($i = 0; $i < count($refParams); $i++) {
                        $pname = $refParams[$i]->getName();
                        $defv = ($refParams[$i]->isDefaultValueAvailable() ? $refParams[$i]->getDefaultValue() : null );
                        if (!$refParams[$i]->canBePassedByValue()) {
                            $pl[] = $this->getParamValue($params, $pname, $i, $defv);
                            $pl[$i] = &$pl[$i];
                            $out_indexes[] = $i;
                        } else {
                            $pl[] = $this->getParamValue($params, $pname, $i, $defv);
                        }
                    }

                    $result = $rfm->invokeArgs($this, $pl);
                    $outs = array();
                    //print_r($out_indexes); print_r($pl);
                    for ($i = 0; $i < count($out_indexes); $i++) {
                        $ind = $out_indexes[$i];
                        $refParams[$ind]->getName();
                        $outs[$refParams[$ind]->getName()] = $pl[$ind];
                    }
                } else {
                    throw new WebAjaxException(__METHOD__, "Method $method is not accessible", 2002);
                }
            } else {
                throw new WebAjaxException(__METHOD__, "Method $method not found", 2001);
            }
        }

        public final function asArray(): array {
            $this->lastOperationData = [
                "methodName" => $this->methodName,
                "methodParams" => $this->methodParams,
                "methodResult" => $this->methodResult,
                "methodOutParams" => $this->methodOutParams,
                "methodDuration" => $this->methodDuration,
                "methodException" => $this->methodException
            ];

            if (is_null($this->methodException)) {
                return [
                    "success" => true,
                    "outputs" => $this->methodOutParams,
                    "result" => $this->methodResult
                ];
            } else {
                return [
                    "success" => false,
                    "text" => $this->methodException["message"]
                ];
            }
        }

        public final function getLastOperationData() {
            return $this->lastOperationData;
        }

        public final function printAsJson($printMode = JSON_PRETTY_PRINT) {
            if (!headers_sent()) {
                header('Content-Type: application/json;charset=utf-8;');
            }
            echo json_encode($this->asArray(), $printMode);
        }

    }

}
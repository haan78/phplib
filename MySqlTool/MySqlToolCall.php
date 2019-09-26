<?php

namespace MySqlTool {

    require_once __DIR__ . "/MySqlToolExeption.php";

    use MySqlTool\MySqlToolDatabaseException;

    class MySqlToolCall {

        private $params;
        private $link;
        private $lastSQL;
        public $setEmptyStringsToNull = true;
        public $autoClose = true;

        public function __construct(\mysqli $link) {
            $this->link = $link;
            $this->params = array();
        }

        public function in($value, $quotes = true) {
            $prm = array(
                "type" => "IN",
                "name" => false,
                "value" => $value,
                "quotes" => $quotes === false ? false : true
            );
            array_push($this->params, $prm);
            return $this;
        }

        public function out($name, $value = null, $quotes = true) {
            $prm = array(
                "type" => "OUT",
                "name" => $name,
                "value" => $value,
                "quotes" => $quotes === false ? false : true
            );
            array_push($this->params, $prm);
            return $this;
        }

        private function valToStr($val, $quotes) {
            if (is_null($val)) {
                return "NULL";
            } elseif ((is_object($val)) || (is_array($val))) {
                return "'" . json_encode($val, JSON_UNESCAPED_UNICODE) . "'";
            } elseif ($val === false) {
                return "0";
            } elseif ($val === true) {
                return "1";
            } elseif (($val === "") && ($this->setEmptyStringsToNull )) {
                return "NULL";
            } else {
                if ($quotes) {
                    //echo "**$val**";
                    return "'" . mysqli_escape_string($this->link, $val) . "'";
                } else {
                    return $val;
                }
            }
        }

        private function generateSQL($procedure, &$isThereOut = false) {
            $set = "";
            $prms = "";
            $select = "";

            for ($i = 0; $i < count($this->params); $i++) {
                if ($i > 0) {
                    $prms .= ",";
                }
                if ($this->params[$i]["type"] === "IN") {
                    $prms .= $this->valToStr($this->params[$i]["value"], $this->params[$i]["quotes"]);
                } elseif ($this->params[$i]["type"] === "OUT") {
                    if (strlen($set) > 0) {
                        $set .= ",";
                        $select .= ",";
                    }
                    $set .= "@" . $this->params[$i]["name"] . "=" . $this->valToStr($this->params[$i]["value"], $this->params[$i]["quotes"]);
                    $select .= "@" . $this->params[$i]["name"] . " AS " . $this->params[$i]["name"];
                    $prms .= "@" . $this->params[$i]["name"];
                }
            }

            $SQL = "CALL " . $procedure . "( " . $prms . " )";
            if (strlen($set) > 0) {
                $isThereOut = true;
                $SQL = "SET " . $set . ";\n"
                        . $SQL . ";\n"
                        . "SELECT " . $select . ";";
            } else {
                $isThereOut = false;
            }

            $this->lastSQL = $SQL;
            return $SQL;
        }

        public function mysqli_call($procedure, &$error_code, &$error_text, &$isThereOut) {

            $queryies = [];

            if (!mysqli_multi_query($this->link, $this->generateSQL($procedure, $isThereOut))) {                
                $error_code = mysqli_errno($this->link);
                $error_text = mysqli_error($this->link);
                return false;
            }

            while (true == true) {
                $r = mysqli_store_result($this->link);
                if ($r instanceof \mysqli_result) {
                    array_push($queryies, $r);
                } elseif (mysqli_errno($this->link) != 0) {
                    $error_code = mysqli_errno($this->link);
                    $error_text = mysqli_error($this->link);
                    return false;
                } else {
                    //?
                }
                if (mysqli_more_results($this->link)) {
                    mysqli_next_result($this->link);
                } else {
                    break;
                }
            }
            return $queryies;
        }

        private function mysqli_exec($sql, $type, &$error_code, &$error_text) {            
            $s = "";
            $j = 0;
            for ($i = 0; $i < strlen($sql); $i++) {
                if ($sql[$i] == "?") {
                    if (isset($this->params[$j]))
                            $s .= $this->valToStr($this->params[$j]["value"], $this->params[$j]["quotes"]);
                    else $s .= "NULL";
                    $j++;
                } else {
                    $s .= $sql[$i];
                }
            }
            $this->lastSQL = $s;
            //echo $this->lastSQL;

            $query = mysqli_query($this->link, $this->lastSQL);           
            if ($query instanceof \mysqli_result) {                
                if (mysqli_num_rows($query) < 0) {
                    return null;
                }
                mysqli_data_seek($query, 0);                
                if ($type == "array") {
                    $arr = array();
                    while ($row = mysqli_fetch_assoc($query)) {
                        array_push($arr, $row);
                    }
                    return $arr;
                } else {
                    $row = mysqli_fetch_array($query);
                    if ($type == "orginal") {
                        return $row[0];
                    } elseif ($type == "int") {
                        return intval($row[0]);
                    } elseif ($type == "float") {
                        return floatval($row[0]);
                    } else {
                        return null;
                    }
                }
            } elseif ($query == true) {
                return mysqli_affected_rows($this->link);
            } else {

                $error_code = mysqli_errno($this->link);
                $error_text = mysqli_error($this->link);
                return false;
            }
        }

        public function exec($sql, $type = "orginal") {
            $error_code = $error_text = null;

            $r = $this->mysqli_exec($sql, $type, $error_code, $error_text);

            $this->params = array();
            if ($this->autoClose) mysqli_close($this->link);

            if ((is_null($error_code)) && (is_null($error_text))) {
                return $r;
            } else {
                throw new MySqlToolDatabaseException($error_text, $error_code, $this->lastSQL);
            }
        }

        public function call($procedure, &$outs, &$queries) {
            $error_code = $error_text = null;
            $isThereOut = false;
            $queryies = $this->mysqli_call($procedure, $error_code, $error_text, $isThereOut);


            if ($queryies === false) {
                if ($this->autoClose) mysqli_close($this->link);
                throw new MySqlToolDatabaseException($error_text, $error_code, $this->lastSQL);
            }

            $this->params = array();
            $outs = [];
            $queries = [];
            for ($i = 0; $i < count($queryies); $i++) {
                $q = [];
                mysqli_data_seek($queryies[$i], 0);
                if (($i == count($queryies) - 1) && ($isThereOut)) {
                    $outs = mysqli_fetch_assoc($queryies[$i]);
                } else {
                    while ($row = mysqli_fetch_assoc($queryies[$i])) {
                        array_push($q, $row);
                    }
                    array_push($queries, $q);
                }
                mysqli_free_result($queryies[$i]);
            }

            if ($this->autoClose) mysqli_close($this->link);
        }

    }

}
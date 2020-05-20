<?php

namespace Web {
    
    class WebUploadException extends \Exception {
        
        private $info;

        public function __construct($info,$message, $code = 0, \Exception $previous = null) {
            $this->info = $info;
            parent::__construct($message, $code, $previous);
        }

        public function __toString() {
            return __CLASS__ . ": [{$this->code}]: {$this->message} / $this->info";
        }
    }

    class Upload {

        private $maximumSize;
        private $allowedExts;

        public function __construct($exts = array('jpeg', 'jpg', 'png', 'gif', 'svg', 'pdf'), $maximumSize = (100 * 1024 * 1024)) {
            $this->maximumSize = $maximumSize;
            $this->allowedExts = $exts;
        }

        public function addAllowedExtension(string $ext) {
            array_push($this->allowedExts, strtolower($ext));
        }

        private function isAllowed(string $ext) {
            return in_array(strtolower($ext), $this->allowedExts);
        }

        public function setMaximumSize($size) {
            $this->maximumSize = intval($size);
        }

        public function save(string $name, string $target, string &$ext) {
            if (isset($_FILES[$name])) {
                $target_info = pathinfo($target);
                $source_info = pathinfo($_FILES[$name]['name']);                
                if ($this->isAllowed($source_info['extension'])) {
                    if ($_FILES[$name]["size"] <= $this->maximumSize) {
                        if (file_exists($target_info['dirname'])) {
                            if (is_writable($target_info['dirname'])) {
                                if (!move_uploaded_file($_FILES[$name]['tmp_name'], $target . "." . $source_info['extension'])) {
                                    throw new \Exception($_FILES[$name]["error"]);
                                }
                                $ext = $source_info['extension'];
                            } else {
                                throw new WebUploadException($target_info['dirname'],"Folder is not writable",3005);
                            }
                        } else {
                            throw new WebUploadException($target_info['dirname'],"Folder is not exist",3004);
                        }
                    } else {
                        throw new WebUploadException($source_info,"File is too big",3003);
                    }
                } else {
                    throw new WebUploadException($source_info,"File extension is not allowed",3002);
                }
            } else {
                throw new WebUploadException($name,"Upload name is not in files",3001);
            }
        }

        public static function jpg($name, $folder) {
            $up = new \Web\Upload(['jpg', 'jpeg']);
            $file = uniqid();
            $ext = "";
            $up->save($name, "$folder/$file", $ext);
            return "$file.$ext";
        }

    }

}

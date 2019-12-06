<?php

class Upload {

    private $maximumSize;    
    private $allowedExts;

    public function __construct($exts = array('jpeg','jpg','png','gif','svg','pdf'),$maximumSize = (100 * 1024 * 1024) ) {
        $this->maximumSize = $maximumSize;
        $this->allowedExts = $exts;
    }

    public function addAllowedExtension(string $ext) {
        array_push($this->allowedExts, strtolower($ext) );
    }

    private function isAllowed(string $ext) {
        return in_array(strtolower($ext), $this->allowedExts);
    }

    public function setMaximumSize($size) {
        $this->maximumSize = intval($size);
    }

    public function save(string $name, string $target) {
        if (isset($_FILES[$name])) {
            $target_info = pathinfo($target);
            $source_info = pathinfo($_FILES[$name]['name']);
            if ($this->isAllowed($source_info['extension'])) {
                if ($_FILES[$name]["size"] <= $this->maximumSize) {
                    if (file_exists($target_info['dirname'])) {
                        if (is_writable($target_info['dirname'])) {                            
                            if (!move_uploaded_file($_FILES[$name]['tmp_name'], $target.".".$source_info['extension'])) {
                                throw new \Exception($_FILES[$name]["error"]);
                            }
                        } else {
                            throw new \Exception("Folder is not writable");
                        }
                    } else {
                        throw new \Exception("Folder is not exist");
                    }
                } else {
                    throw new \Exception("File is too big");
                }
            } else {
                throw new \Exception("File extension is not allowed");
            }
        } else {
            throw new \Exception("Upload name is not in files");
        }
    }
    
    public static function jpg($name,$folder) {
        $up = new Upload(['jpg','jpeg']);
        $file = uniqid(true);
        $up->save($name,"$folder/$file");
        return $file;
    }

}

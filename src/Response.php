<?php
namespace Greenpath\GuzzleKrac;

class Response extends GuzzleKrac implements \JsonSerializable {
    public $success;
    public $error;
    public $data;
    public $messages;
    public $status;
    public $headers;

    public function __construct(array $array)
    {
        if(!empty($array)){
            foreach ($array as $key => $value){
                if(!empty($value)){
                    $this->$key = $value;

                    if(empty($this->$key)){
                        unset($this->$key);
                    }
                }
            }
        }

        if($this->error) {
            unset($this->success);
        } else {
            unset($this->error);
        }
    }

    public function _toArray()
    {
        return (array)$this;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function _toJson()
    {
        return $this->jsonSerialize();
    }
}
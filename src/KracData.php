<?php
namespace Greenpath\GuzzleKrac;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;

abstract class KracData
{
    protected $data;

    public function __construct(array $parameters)
    {
        $this->data = (!empty($parameters['data']) ? $parameters['data'] : NULL);
    }

    public function data(array $data){
        return (!empty($this->data) ? array_merge($this->data, $data) : $data);
    }

    public function toArray() : array
    {
        return json_decode($this->data, true);
    }
}
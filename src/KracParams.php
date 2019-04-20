<?php
namespace Greenpath\GuzzleKrac;

use Greenpath\GuzzleKrac\Request;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class KracParams extends KracData implements Request
{
    public $request;

    public function __construct($key, $secret)
    {
        $this->credentials($key, $secret);
    }

    /**
     * Set Credentials, add to the query index
     * @param string $key
     * @param string $secret
     */
    public function credentials(string $key, string $secret)
    {
        $this->request['query'] = array('api_key' => $key, 'api_secret' => $secret);
    }

    /**
     * Build query params, merge if exisiting values
     * @param array $queries
     */
    public function query(array $queries)
    {
        $this->request['query'] = (!empty($this->request['query']) ? array_merge($this->request['query'], $queries) : $queries);
    }

    /**
     * Build headers params, merge if exisiting values
     * @param array $headers
     */
    public function headers(array $headers)
    {
        $this->request['headers'] = (!empty($this->request['headers']) ? array_merge($this->request['headers'], $headers) : $headers);
    }

    /**
     * Loop filters index and append to query param after formatting for 3rd party composer package on service sied
     * @param array $filters
     */
    public function filters(array $filters)
    {
        if(!empty($filters)){
            foreach($filters as $k => $v){
                $this->request['query']['filter['.$k.']'] = $v;
            }
        }
    }

    /**
     * Build form params or merge with existing (token is added if form params exist for the request)
     * @param array $formparams
     */
    public function form_params(array $formparams)
    {
        $this->request['form_params'] = (!empty($this->request['form_params']) ? array_merge($this->request['form_params'], $formparams) : $formparams);
    }

    /**
     * Populate required variables based on array parameters, return request array
     * @param array $array
     * @return array
     */
    public function get(array $array = null): array
    {
        if(!empty($array)){
            foreach($array as $key => $value){
                $this->$key($value);
            }
        }

        return $this->request;
    }

    /**
     * Return existing array item
     * @param string $item
     * @return array|string
     */
    public function item(string $item)
    {
        return isset($this->request[$name]) ? $this->request[$name] : null;
    }

    /**
     * Does request array contain index
     * @param string $item
     * @return boolean
     */
    public function has(string $name)
    {
        return !empty($this->request[$name]) ? true : false;
    }
}
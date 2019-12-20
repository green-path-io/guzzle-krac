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
     * Build form params or merge with existing (token is added if form params exist for the request)
     * @param array $multiparts
     */
    public function multipart(array $multiparts)
    {
        if(!empty($multiparts)){
            foreach($multiparts as $k => $v){
                if(!is_array($v)){
                    $this->request['multipart'][$k] = ['name' => $k, 'contents' => (is_file($v) ? file_get_contents($v->getRealPath()) : $v)];
                    if(is_file($v)){
                        $this->request['multipart'][$k]['filename'] = $v->getClientOriginalName();
                    }
                    continue;
                }

                foreach($v as $multikey => $multivalue){
                    $multiname = $k . '[' .$multikey . ']' . (is_array($multivalue) ? '[' . key($multivalue) . ']' : '' ) . '';
                    $this->request['multipart'][$k] = ['name' => $multiname, 'contents' => (is_array($multivalue) ? reset($multivalue) : $multivalue)];
                }
            }
        }
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
                if(!empty($value)){
                    $this->$key($value);
                }
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
        return isset($this->request[$item]) ? $this->request[$item] : null;
    }

    /**
     * Does request array contain index
     * @param string $name
     * @return boolean
     */
    public function has(string $name)
    {
        return !empty($this->request[$name]) ? true : false;
    }

    /**
     * Remove an index from the request array
     * @param string $array
     * @param string $name
     * @return boolean
     */
    public function remove(string $array, string $name)
    {
        if(!empty($this->request[$array][$name])){
            unset($this->request[$array][$name]);
            return true;
        }

        return false;
    }

    /**
     * Get Query Parameters but remove sensitive information
     * @return array
     */
    public function getQueryParams(){
        if(!empty($this->request['query']['api_key'])){
            $this->remove('query', 'api_key');
        }
        if(!empty($this->request['query']['api_secret'])){
            $this->remove('query', 'api_secret');
        }

        return $this->request['query'];
    }
}
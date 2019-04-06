<?php
namespace Greenpath\GuzzleKrac\GuzzleKrac;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GuzzleKrac {
    private $rest_uri;
    private $key;
    private $secret;
    private $headers;
    private $required_params;

    public function __construct(){
        $this->rest_uri = env('GZ_REST_URI', NULL);
        $this->rest_path = env('GZ_REST_PATH', NULL);
        $this->key = env('GZ_REST_KEY', NULL);
        $this->secret = env('GZ_REST_SECRET', NULL);

        $this->required_params = array(
            'api_key' => $this->key,
            'api_secret' => $this->secret
        );
    }

    /**
     * @return Client
     */
    private function initiate()
    {
        return new Client(['base_uri' => $this->rest_uri]);
    }

    /**
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    private function get(string $url, array $options = []): ResponseInterface
    {
        return $this->initiate()->get($url, $options);
    }

    /**
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    private function delete(string $url, array $options = []): ResponseInterface
    {
        return $this->initiate()->delete($url, $options);
    }

    /**
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    private function patch(string $url, array $options = []): ResponseInterface
    {
        return $this->initiate()->patch($url, $options);
    }

    /**
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    private function put(string $url, array $options = []): ResponseInterface
    {
        return $this->initiate()->put($url, $options);
    }

    /**
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    private function post(string $url, array $options = []): ResponseInterface
    {
        return $this->initiate()->post($url, $options);
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function formatGetParameters(array $parameters): array
    {
        return ['query' => $parameters];
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function formatRequestParameters(array $parameters): array
    {
        return ['form_params' => $parameters];
    }

    private function request(string $method, string $url, array $options = [])
    {
        return $this->initiate()->request($method, $url, $options);
    }

    /**
     * Send a request to the api and return the response
     * @param string $method
     * @param string $url
     * @param array $parameters
     * @param array $pagination
     * @return array
     */
    public function doRequest(string $method = "get", string $url = "", array $parameters = [], array $pagination = []): array
    {
        $fullUrl = $this->formatUrl($url);

        $queryParameters = $this->getQueryParameters($method, array_merge($this->required_params, array_merge($this->formatPagination($pagination), $this->formatFilters($parameters))));
        return $this->responseHandler($this->$method($fullUrl, $this->mergeHeaders($queryParameters)));
    }

    /**
     * Merge any headers from the api-wrapper config file with any custom headers for this request
     * @param array $parameters
     * @return array
     */
    private function mergeHeaders(array $parameters): array
    {
        $headers['headers'] = $this->headers;
        return array_merge($parameters, $headers);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return array
     */
    private function getQueryParameters(string $method, array $parameters): array
    {
        switch ($method) {
            case "get":
                return $this->formatGetParameters($parameters);
                break;
            default:
                return $this->formatRequestParameters($parameters);
                break;
        }
    }

    private function formatFilters(array $parameters):array
    {
        if(is_array($parameters['filters']) && $parameters['filters']){
            foreach($parameters['filters'] as $key => $value){
                $parameters['filter['.$key.']'] = $value;
            }
            unset($parameters['filters']);
        }

        return $parameters;
    }

    private function formatPagination(array $array):array
    {
        return [
            'page' => (!empty($array['page']) ? $array['page'] : 1),
            'take' => (!empty($array['take']) ? $array['take'] : 12),
        ];
    }

    private function formatUrl(string $url)
    {
        $fullUrl = $this->rest_path;
        if(!empty($url)) {
            $fullUrl .= $url;
        }

        return $fullUrl;
    }

    private function responseHandler($response){
        return json_decode($response->getBody(), true);
    }
}
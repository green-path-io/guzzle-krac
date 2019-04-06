<?php
namespace Greenpath\GuzzleKrac;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
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
        $parameters = $this->buildParameters($parameters, $pagination);

        try {
            $response = $this->responseHandler($this->$method($fullUrl, $parameters));
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->responseHandler($e->getResponse());
            }
        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                return $this->responseHandler($e->getResponse());
            }
        }

        return $response;
    }

    private function buildParameters(array $parameters, array $pagination = null):array
    {
        $parameters = $this->formatHeaderParameters($parameters);
        $parameters = $this->formatFilters($parameters);
        $parameters = $this->formatQueryParameters($parameters, $pagination);

        return $parameters;
    }

    private function formatHeaderParameters(array $parameters):array
    {
        if(!empty($parameters['headers']) || !empty($this->headers)) {
            $parameters['headers'] = (isset($parameters['headers']) ? array_merge($parameters['headers'], $this->headers) : $this->headers);
        }
        return $parameters;
    }

    private function formatQueryParameters(array $parameters, array $pagination = null):array
    {
        $parameters['query'] = (isset($parameters['query']) ? array_merge($parameters['query'], $this->required_params) : $this->required_params);
        $parameters['query'] = array_merge($parameters['query'], (!empty($pagination) ? $pagination : array()));

        return $parameters;
    }

    private function formatFilters(array $parameters):array
    {
        if(!empty($parameters['filters']) && is_array($parameters['filters'])){
            foreach($parameters['filters'] as $key => $value){
                $parameters['query']['filter['.$key.']'] = $value;
            }
            unset($parameters['filters']);
        }

        return $parameters;
    }

    private function formatPagination(array $array):array
    {
        return [
            'page' => (!empty($array['page']) && is_int($array['page']) ? $array['page'] : 1),
            'take' => (!empty($array['take']) && is_int($array['take']) ? $array['take'] : 12),
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
        $content = json_decode($response->getBody()->getContents(), true);

        if(!empty($content['data'])){
            return [
                'success' => 1,
                'data' => $content['data'],
                'headers' => $response->getHeaders(),
                'status' => $response->getStatusCode()
            ];
        } else if($content['error']) {
            return [
                'error' => $content['error'],
                'messages' => $content['message'],
                'headers' => $response->getHeaders(),
                'status' => $response->getStatusCode()
            ];
        } else {
            return [
                'error' => 1,
                'messages' => 'response assignment failure',
                'headers' => $response->getHeaders(),
                'status' => 500
            ];
        }
    }
}
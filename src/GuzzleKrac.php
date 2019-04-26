<?php
namespace Greenpath\GuzzleKrac;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\App;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Message\ResponseInterface;

class GuzzleKrac {
    private $rest_url;
    private $key;
    private $secret;
    private $kracurl;
    private $kracparams;

    public function __construct(){
        $this->rest_url = env('GZ_REST_URL', NULL);
        $this->key = env('GZ_REST_KEY', NULL);
        $this->secret = env('GZ_REST_SECRET', NULL);
        $this->showheaders = env('GZ_REST_SHOW_HEADERS', false);
        $this->keyname = env('GZ_REST_KEY_NAME', 'token');
        $this->kracparams = new KracParams($this->key, $this->secret);
    }

    /**
     * Init the Guzzle Client
     * @return Client
     */
    private function initiate()
    {
        return new Client(['base_uri' => $this->getURI()]);
    }

    /**
     * Populate the URL Object by parsing the rest url and path
     * @return array kracurl
     */
    private function buildUrl(string $apipath)
    {
        $parser = new \League\Uri\Parser();
        $this->kracurl = $parser($this->rest_url.''.$apipath);

        return $this->kracurl;
    }

    /**
     * Return back the domain url with the scheme
     * @return string scheme:host
     */
    private function getURI(){
        return $this->kracurl['scheme'].'://'.$this->kracurl['host'];
    }

    /**
     * Return back the domain url with the scheme and path
     * @return string scheme:host:path
     */
    private function getURL(){
        return $this->kracurl['scheme'].'://'.$this->kracurl['host'].$this->kracurl['path'];
    }

    /**
     * Return back the domains path only
     * @return string  path
     */
    private function getPath(){
        return $this->kracurl['path'];
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
     * @param string $mthod
     * @param string $url
     * @param array $options
     * @return ResponseInterface
     */
    private function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->initiate()->request($method, $url, $options);
    }

    /**
     * Build the response object based on the response once validating the token
     * @param ResponseInterface $response
     * @return Response
     */
    private function responseHandler(ResponseInterface $response)
    {
        $results = json_decode($response->getBody()->getContents());
        $validation = (App::environment('production') ? $this->validateToken($results->token): false);
        if(is_string($validation)){
            $results->messages = array('warning' => $validation);
            $validation = false;
        }

        if(!empty($response->getStatusCode() == 200)){
            return new Response([
                'success' => 1,
                'data' => $results->data,
                'messages' => ($validation && !empty($results->messages) ? $results->messages : (!empty($results->messages) ? $results->messages : 'no messaging present.') ),
                'headers' => ($this->showheaders ? $response->getHeaders() : false),
                'status' => $response->getStatusCode()
            ]);
        } else {
            return new Response([
                'error' => (!empty($results->error) ? $results->error : $response->getReasonPhrase()),
                'messages' => ($validation && !empty($results->messages) ? $results->messages : (!empty($results->messages) ? $results->messages : 'response assignment failure') ),
                'headers' => ($this->showheaders ? $response->getHeaders() : false),
                'status' => (!empty($response->getStatusCode()) && $response->getStatusCode() !== 500 ? $response->getStatusCode() : 500)
            ]);
        }
    }

    /**
     * Create the token for the request object, keyname for array can be defined
     * @param string $keyname
     * @param string $sign
     * @return array
     */
    private function setToken(string $keyname, string $sign) : array
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->setIssuer(config('app.url'))
            ->setAudience($this->getURI())
            ->setIssuedAt(time())
            ->setExpiration(time() + 100)
            ->sign($signer, $sign)
            ->getToken()->__toString();

        return [$keyname => $token];
    }

    /**
     * Valdiate the return token from the response
     * @param string $token
     * @return boolean|string
     */
    private function validateToken(string $token){
        if(!empty($token)){
            $signer = new Sha256();
            $token = (new Parser())->parse((string) $token); // Parses from a string

            if($token->verify($signer, $this->secret)){
                $data = new ValidationData();
                $data->setIssuer($this->getURI());
                $data->setAudience(config('app.url'));

                if($token->validate($data)){
                    return true;
                } else {
                    return 'token not originating from original issuer / audience.';
                }
            } else {
                return 'token is invalid or expired.';
            }
        } else {
            return 'token could not be located.';
        }
    }

    /**
     * Send a request to the api and return the response
     * @param string $method
     * @param string $path
     * @param array $parameters
     * @return Response
     */
    public function doRequest(string $method = "get", string $path = "", array $parameters = []): Response
    {
        $requesturl = $this->buildUrl($path);
        if (App::environment('production')){
            $this->kracparams->form_params($this->setToken($this->keyname, $this->secret));
        }

        try {
            $response = $this->responseHandler($this->$method($requesturl['path'], $this->kracparams->get($parameters)));
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
}
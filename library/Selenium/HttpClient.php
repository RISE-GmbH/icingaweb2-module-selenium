<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */
/* Icinga Web 2 Repository Module | (c) 2022  MoreAmazingNick| GPLv2+ */

namespace Icinga\Module\Selenium;

use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;

class HttpClient
{
    /**
     * The cURL handle of this RestApiClient
     *
     * @var resource
     */
    protected $curl;

    /**
     * The host of the API
     *
     * @var string
     */
    protected $host;

    /**
     * The name of the user to access the API with
     *
     * @var string
     */
    protected $user;

    /**
     * The password for the user the API is accessed with
     *
     * @var string
     */
    protected $pass;

    /**
     * The path of a file holding one or more certificates to verify the peer with
     *
     * @var string
     */
    protected $certificatePath;

    /**
     * Do not Check ssl at all
     *
     * @var string
     */
    protected $insecure;

    /**
     * Create a new RestApiClient
     *
     * @param   string  $host               The host of the API
     * @param   string  $user               The name of the user to access the API with
     * @param   string  $pass               The password for the user the API is accessed with
     * @param   string  $certificatePath    The path of a file holding one or more certificates to verify the peer with
     * @param   boolean  $insecure          Do not Check ssl at all
     */
    public function __construct($host, $user = null, $pass = null, $certificatePath = null, bool $insecure = false)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->certificatePath = $certificatePath;
        $this->insecure = $insecure;
    }

    /**
     * Return the cURL handle of this RestApiClient
     *
     * @return  resource
     */
    public function getConnection()
    {
        if ($this->curl === null) {
            $this->curl = $this->createConnection();
        }

        return $this->curl;
    }

    /**
     * Create and return a new cURL handle for this RestApiClient
     *
     * @return  resource
     */
    protected function createConnection()
    {
        $curl = curl_init();
        $config['useragent'] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36";

        curl_setopt($curl, CURLOPT_USERAGENT, $config['useragent']);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($this->certificatePath !== null) {
            curl_setopt($curl, CURLOPT_CAINFO, $this->certificatePath);
        }

        if ($this->user !== null && $this->pass !== null) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        }
        if ($this->insecure) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        }
        return $curl;
    }

    /**
     * Send the given request and return its response
     *
     * @param   Request  $request
     *
     * @return  Response
     *
     * @throws  CustomException            In case an error occured while handling the request
     */
    public function request($request)
    {
        $scheme = strpos($this->host, '://') !== false ? '' : 'https://';
        $path = '/' . join('/', array_map('rawurlencode', explode('/', ltrim($request->getPath(), '/'))));
        $query = ($request->getParams()->isEmpty() ? '' : ('?' . (string) $request->getParams()));

        $curl = $this->getConnection();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $request->getHeaders());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($curl, CURLOPT_URL, $scheme . $this->host . $path . $query);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getPayload());

        $result = curl_exec($curl);
        if ($result === false) {
            $restApiException = new CustomException(curl_error($curl));
            $restApiException->setErrorCode(curl_errno($curl));
            throw $restApiException;
        }

        $header = substr($result, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));

        $result = substr($result, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
        $statusCode = 0;
        $hasNext = false;
        foreach (explode("\r\n", $header) as $headerLine) {
            // The headers are inspected manually because curl_getinfo($curl, CURLINFO_HTTP_CODE)
            // returns only the first status code. (e.g. 100 instead of 200)
            $matches = array();
            if (preg_match('/^HTTP\/[0-9.]+ ([0-9]+)/', $headerLine, $matches)) {
                $statusCode = (int) $matches[1];
            }

            $matches = array();
            if (preg_match('/link: .+?rel="next"/', $headerLine, $matches)) {
                $hasNext=true;
            }

        }

        $response = new Response($statusCode);
        if ($result) {
            if($hasNext){
                $response->setHasNext(true);
                Logger::info("hasnext");
            }
            $response->setPayload($result);
            $response->setContentType(curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        }

        return $response;
    }

    /**
     * Send the given request and return its response
     *
     * @param   Request  $request
     *
     * @return  boolean
     *
     * @throws  CustomException            In case an error occured while handling the request
     */
    public function download($request, $saveTo)
    {
        $scheme = strpos($this->host, '://') !== false ? '' : 'https://';
        $path = '/' . join('/', array_map('rawurlencode', explode('/', ltrim($request->getPath(), '/'))));
        $query = ($request->getParams()->isEmpty() ? '' : ('?' . (string) $request->getParams()));

        $curl = $this->getConnection();
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $request->getHeaders());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($curl, CURLOPT_URL, $scheme . $this->host . $path . $query);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getPayload());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        $fp = fopen($saveTo, "wb");
        curl_setopt($curl, CURLOPT_FILE, $fp);

        $result = curl_exec($curl);
        fclose($fp);
        if ($result === false) {
            $restApiException = new CustomException(curl_error($curl));
            $restApiException->setErrorCode(curl_errno($curl));
            throw $restApiException;
        }

        return $result;
    }

    /**
     * Render and return a human readable error message for the given error document
     *
     * @return  string
     *
     * @todo    Parse Elasticsearch 2.x structured errors
     */
    public function renderErrorMessage(Response $response)
    {
        try {
            $errorDocument = $response->json();
        } catch (IcingaException $e) {
            return sprintf('%s: %s',
                $e->getMessage(),
                $response->getPayload()
            );
        }

        if (! isset($errorDocument['error'])) {
            return sprintf('Elasticsearch unknown json error %s: %s',
                $response->getStatusCode(),
                $response->getPayload()
            );
        }

        if (is_string($errorDocument['error'])) {
            return $errorDocument['error'];
        }

        return sprintf('Elasticsearch json error %s: %s',
            $response->getStatusCode(),
             json_encode($errorDocument['error'])
        );
    }

}

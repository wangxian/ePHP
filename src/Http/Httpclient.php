<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace ePHP\Http;

/**
 * Make a Http Request
 *
 * 修改至 https://github.com/dsyph3r/curl-php/blob/master/lib/Network/Curl/Curl.php
 */
class Httpclient
{
    /**
     * Constants for available HTTP methods.
     */
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    /**
     * cURL handle
     *
     * @var resource handle
     */
    protected $curl;

    /**
     * Create the cURL resource.
     */
    public function __construct()
    {
        $this->curl = curl_init();
    }

    /**
     * Clean up the cURL handle.
     */
    public function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * Get the cURL handle.
     *
     * @return resource cURL handle
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * Make a HTTP GET request.
     *
     * @param string $url
     * @param mixed $params
     * @param array $options
     *
     * @return HttpclientResponse
     */
    public function get($url, $params = array(), $options = array())
    {
        return $this->request($url, self::GET, $params, $options);
    }

    /**
     * Make a HTTP POST request.
     *
     * @param string $url
     * @param mixed $params
     * @param array $options
     *
     * @return HttpclientResponse
     */
    public function post($url, $params = array(), $options = array())
    {
        return $this->request($url, self::POST, $params, $options);
    }

    /**
     * Make a HTTP PUT request.
     *
     * @param string $url
     * @param mixed $params
     * @param array $options
     *
     * @return HttpclientResponse
     */
    public function put($url, $params = array(), $options = array())
    {
        return $this->request($url, self::PUT, $params, $options);
    }

    /**
     * Make a HTTP PATCH request.
     *
     * @param string $url
     * @param mixed $params
     * @param array $options
     *
     * @return HttpclientResponse
     */
    public function patch($url, $params = array(), $options = array())
    {
        return $this->request($url, self::PATCH, $params, $options);
    }

    /**
     * Make a HTTP DELETE request.
     *
     * @param string $url
     * @param mixed $params
     * @param array $options
     *
     * @return HttpclientResponse
     */
    public function delete($url, $params = array(), $options = array())
    {
        return $this->request($url, self::DELETE, $params, $options);
    }

    /**
     * Make a HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param mixed $params
     * @param array $options
     *
     * @return HttpclientResponse
     * @noinspection SpellCheckingInspection
     */
    protected function request($url, $method = self::GET, $params = array(), $options = array())
    {
        if (is_array($params)) {
            if ($method === self::GET || $method === self::DELETE) {
                $url .= (stripos($url, '?') ? '&' : '?').http_build_query($params);
                $params = '';
            } elseif (($method === self::POST || $method === self::PUT || $method === self::PATCH)
                && (empty($options['files']) && empty($options['json']) )
                && !is_string($params)
            ) {
                $params = http_build_query($params);
            }
        }

        curl_setopt($this->curl, CURLOPT_HEADER, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->curl, CURLOPT_URL, $url);

        // 使用证书
        if (isset($options['sslcert_path']) && isset($options['sslkey_path'])) {
            if (!file_exists($options['sslcert_path']) || !file_exists($options['sslkey_path'])) {
                throw new \Exception('Cert file is not correct');
            }

            // 设置证书
            // 使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);

            // 严格校验
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($this->curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($this->curl, CURLOPT_SSLCERT, $options['sslcert_path']);
            curl_setopt($this->curl, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($this->curl, CURLOPT_SSLKEY, $options['sslkey_path']);
        } else {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        }

        // Check for files
        if (isset($options['files']) && count($options['files'])) {
            foreach ($options['files'] as $index => $file) {
                $params[$index] = $this->createCurlFile($file);
            }

            version_compare(PHP_VERSION, '5.5', '>') || curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, false);

            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        } else {
            if (isset($options['json'])) {
                $params = json_encode($params, JSON_UNESCAPED_UNICODE);
                $options['headers'][] = 'content-type:application/json';
            }

            if (!empty($params)) {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
            }
        }

        $is_sent_ua = false;
        // Check for custom headers
        if (isset($options['headers']) && count($options['headers'])) {
            foreach ($options['headers'] as $value) {
                if ($value == $is_sent_ua) {
                    $is_sent_ua = true;
                }
            }
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Check for custom headers
        if (isset($options['timeout'])) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $options['timeout']);
        }

        // Check for basic auth
        if (isset($options['auth']['type']) && 'basic' === $options['auth']['type']) {
            curl_setopt($this->curl, CURLOPT_USERPWD, $options['auth']['username'].':'.$options['auth']['password']);
        }

        // follow url redirect 301/302
        // 注意：服务器端一般都需要访问携带ua，否则不能访问，从修改支持 follow 起，httpclient 默认都会发送ua
        if (!empty($options['follow'])) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            // 最大跳转次数
            curl_setopt($this->curl, CURLOPT_MAXREDIRS, 3);
        }

        // always send user agent header
        if (!$is_sent_ua) {
            curl_setopt($this->curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) ephp-httpclient");
        }

        $response = $this->doCurl();

        // Separate headers and body
        $headerSize = $response['curl_info']['header_size'];
        $header = trim(substr($response['response'], 0, $headerSize));
        $body = trim(substr($response['response'], $headerSize));

        return new HttpclientResponse($body, $response['curl_info']['http_code'], $this->splitHeaders($header), $response['curl_info']['content_type'], $response['curl_info']);
    }

    /**
     * make cURL file.
     *
     * @param string $filename
     *
     * @return \CURLFile|string
     */
    protected function createCurlFile($filename)
    {
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }

        return "@$filename;filename=".basename($filename);
    }

    /**
     * Split the HTTP headers.
     *
     * @param string $rawHeaders
     *
     * @return array
     */
    protected function splitHeaders($rawHeaders)
    {
        $headers = array();

        $lines = explode("\n", $rawHeaders);
        $headers['HTTP'] = array_shift($lines);

        foreach ($lines as $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }

        return $headers;
    }

    /**
     * Perform the Curl request.
     *
     * @return array
     */
    protected function doCurl()
    {
        $response = curl_exec($this->curl);

        if (curl_errno($this->curl)) {
            throw new \Exception(curl_error($this->curl), 1);
        }

        $curlInfo = curl_getinfo($this->curl);

        return array(
            'curl_info' => $curlInfo,
            'response' => $response,
        );
    }
}

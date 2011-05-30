<?php
/**
 * Curl Scraper class
 *
 * Contains helpful methods for scraping and parsing data from external websites
 *
 * Partially based on Sean Huber's curl wrapper
 *
 * @link http://github.com/shuber/curl
 */
class Curl {

/**
 * The file to read and write cookies to for requests
 *
 * @var string
 **/
    public static $cookie_file;

/**
 * Determines whether or not requests should follow redirects
 *
 * @var boolean
 **/
    public static $follow_redirects = true;

/**
 * An associative array of headers to send along with requests
 *
 * @var array
 **/
    public static $headers = array();

/**
 * An associative array of CURLOPT options to send along with requests
 *
 * @var array
 **/
    public static $options = array();

/**
 * The referer header to send along with requests
 *
 * @var string
 **/
    public static $referer;

/**
 * The user agent to send along with requests
 *
 * @var string
 **/
    public static $user_agent;

/**
 * Stores an error string for the last request if one occurred
 *
 * @var string
 * @access protected
 **/
    protected static $error;

/**
 * Stores the timeout length for this request
 *
 * @var string
 * @access protected
 */
    protected static $timeout = 50;

/**
 * Stores resource handle for the current CURL request
 *
 * @var resource
 * @access protected
 **/
    protected static $request;

/**
 * Holds an array of user agents that we may randomly select from
 */
    protected static $possible_ua = array(
      'Mozilla/5.0 (X11; U; Linux x86_64; pl-PL; rv:2.0) Gecko/20110307 Firefox/4.0',
      'Mozilla/5.0 (X11; U; Linux i686; en-GB; rv:2.0) Gecko/20110404 Fedora/16-dev Firefox/4.0',
      'Mozilla/5.0 (X11; Arch Linux i686; rv:2.0) Gecko/20110321 Firefox/4.0',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2.3) Gecko/20100401 Firefox/4.0 (.NET CLR 3.5.30729)',
      'Mozilla/5.0 (Windows NT 6.1; rv:2.0) Gecko/20110319 Firefox/4.0',
      'Mozilla/5.0 (Windows NT 6.1; rv:1.9) Gecko/20100101 Firefox/4.0',
      'Mozilla/5.0 (X11; U; Linux i686; pl-PL; rv:1.9.0.2) Gecko/20121223 Ubuntu/9.25 (jaunty) Firefox/3.8',
      'Mozilla/5.0 (X11; U; Linux i686; pl-PL; rv:1.9.0.2) Gecko/2008092313 Ubuntu/9.25 (jaunty) Firefox/3.8',
      'Mozilla/5.0 (X11; U; Linux i686; it-IT; rv:1.9.0.2) Gecko/2008092313 Ubuntu/9.25 (jaunty) Firefox/3.8',
      'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Mozilla/5.0 (X11; U; Linux i686; it-IT; rv:1.9.0.2) Gecko/2008092313 Ubuntu/9.25 (jaunty) Firefox/3.8',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; hu; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.1',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; es-ES; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.1',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; es-ES; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB7.0 ( .NET CLR 3.5.30729)',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; es-ES; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (.NET CLR 3.5.30729)',
      'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; Tablet PC 2.0; InfoPath.3; .NET4.0C; .NET4.0E)',
      'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0',
      'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/11.0.696.57)',
      'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0) chromeframe/10.0.648.205',
      'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0; chromeframe/11.0.696.57)',
      'Mozilla/5.0 ( ; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
      'Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 5.1; Trident/5.0)'
    );

/**
 * Initializes a Curl object
 *
 * Sets the $cookie_file to "curl_cookie.txt" in the current directory
 * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)' otherwise
 **/
    public static function init($user_agent = false, $cookie_file = null) {
        self::$cookie_file = tempnam(sys_get_temp_dir(), $cookie_file);

        if ($user_agent) {
            self::$user_agent = $user_agent;
        } else {
            self::randomizeUserAgent();
        }
    }

    public static function randomizeUserAgent() {
        self::$user_agent = self::$possible_ua[array_rand(self::$possible_ua)];
    }

/**
 * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
 *
 * Returns a CurlResponse object if the request was successful, false otherwise
 *
 * @param string $url
 * @param array|string $vars
 * @return CurlResponse object
 **/
    public static function delete($url, $vars = array()) {
        return self::request('DELETE', $url, $vars);
    }

/**
 * Returns the error string of the current request if one occurred
 *
 * @return string
 **/
    public static function error() {
        return self::$error;
    }

/**
 * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
 *
 * Returns a CurlResponse object if the request was successful, false otherwise
 *
 * @param string $url
 * @param array|string $vars
 * @return CurlResponse
 **/
    public static function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return self::request('GET', $url);
    }

/**
 * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
 *
 * Returns a CurlResponse object if the request was successful, false otherwise
 *
 * @param string $url
 * @param array|string $vars
 * @return CurlResponse
 **/
    public static function head($url, $vars = array()) {
        return self::request('HEAD', $url, $vars);
    }

/**
 * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
 *
 * @param string $url
 * @param array|string $vars
 * @return CurlResponse|boolean
 **/
    public static function post($url, $vars = array()) {
        return self::request('POST', $url, $vars);
    }

/**
 * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
 *
 * Returns a CurlResponse object if the request was successful, false otherwise
 *
 * @param string $url
 * @param array|string $vars
 * @return CurlResponse|boolean
 **/
    public static function put($url, $vars = array()) {
        return self::request('PUT', $url, $vars);
    }

/**
 * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
 *
 * Returns a CurlResponse object if the request was successful, false otherwise
 *
 * @param string $method
 * @param string $url
 * @param array|string $vars
 * @return CurlResponse|boolean
 **/
    public static function request($method, $url, $vars = array()) {
        self::$error = null;
        self::$request = curl_init();
        if (is_array($vars)) $vars = http_build_query($vars, '', '&');

        self::set_request_method($method);
        self::set_request_options($url, $vars);
        self::set_request_headers();

        $response = curl_exec(self::$request);

        if ($response) {
            $response = new CurlResponse($response);
        } else {
            self::$error = curl_errno(self::$request).' - '.curl_error(self::$request);
        }

        curl_close(self::$request);

        return $response;
    }

/**
 * Formats and adds custom headers to the current request
 *
 * @return void
 * @access protected
 **/
    protected static function set_request_headers() {
        $headers = array();
        foreach (self::$headers as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }
        curl_setopt(self::$request, CURLOPT_HTTPHEADER, $headers);
    }

/**
 * Set the associated CURL options for a request method
 *
 * @param string $method
 * @return void
 * @access protected
 **/
    protected static function set_request_method($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt(self::$request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt(self::$request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt(self::$request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt(self::$request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

/**
 * Sets the CURLOPT options for the current request
 *
 * @param string $url
 * @param string $vars
 * @return void
 * @access protected
 **/
    protected static function set_request_options($url, $vars) {
        curl_setopt(self::$request, CURLOPT_URL, $url);
        if (!empty($vars)) curl_setopt(self::$request, CURLOPT_POSTFIELDS, $vars);

        // Set some default CURL options
        curl_setopt(self::$request, CURLOPT_HEADER, true);
        curl_setopt(self::$request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$request, CURLOPT_USERAGENT, self::$user_agent);
        if (self::$cookie_file) {
            curl_setopt(self::$request, CURLOPT_COOKIEFILE, self::$cookie_file);
            curl_setopt(self::$request, CURLOPT_COOKIEJAR, self::$cookie_file);
        }
        if (self::$follow_redirects) curl_setopt(self::$request, CURLOPT_FOLLOWLOCATION, true);
        if (self::$referer) curl_setopt(self::$request, CURLOPT_REFERER, self::$referer);

        // Set any custom CURL options
        foreach (self::$options as $option => $value) {
            curl_setopt(self::$request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }

}
/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
**/
class CurlResponse {

/**
 * The body of the response without the headers block
 *
 * @var string
 **/
    public $body = null;

/**
 * An associative array containing the response's headers
 *
 * @var array
 **/
    public $headers = array();

/**
 * Accepts the result of a curl request as a string
 *
 * <code>
 * $response = new CurlResponse(curl_exec($curl_handle));
 * echo $response->body;
 * echo $response->headers['Status'];
 * </code>
 *
 * @param string $response
 **/
    function __construct($response) {
        // Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        // Extract headers from response
        preg_match_all($pattern, $response, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        // Remove headers from the response body
        $this->body = str_replace($headers_string, '', $response);

        // Extract the version and status from the first header
        $version_and_status = array_shift($headers);
        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);
        $this->headers['Http-Version'] = $matches[1];
        $this->headers['Status-Code'] = $matches[2];
        $this->headers['Status'] = $matches[2].' '.$matches[3];

        // Convert headers into an associative array
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->headers[$matches[1]] = $matches[2];
        }
    }

/**
 * Returns the response body
 *
 * <code>
 * $curl = new Curl;
 * $response = $curl->get('google.com');
 * echo $response;  # => echo $response->body;
 * </code>
 *
 * @return string
 **/
    function __toString() {
        return $this->body;
    }

}
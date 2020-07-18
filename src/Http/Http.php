<?php
/** @noinspection PhpUnused */

namespace ePHP\Http;

use ePHP\Exception\CommonException;

/**
 * HTTP相关工具
 *
 * <pre>
 *  * -------- 使用示例 ------------
 * Http::limitRefresh(5); //5s内不刷新浏览器
 * Http::download('/home/a.zip');//下载 /home/a.zip 文件
 * Http::download('/home/a.zip','hello');//下载时显示 'hello'
 * Http::download('','test.txt','download content');//将字符串 "download content" 作为文件内容下载
 * echo Http::clientIp();//121.102.0.85
 * echo Http::clientArea();//江西省赣州市 电信
 * Http::setFormCache(); //设置表单在返回时不清空
 *
 * // Http::sendRequest 说明
 * 1:以GET方式请求得到 http://a.com/get.php?c=1&b=2 页面的内容
 * $url = 'http://a.com/get.php?c=1&b=2';
 * echo Http::sendRequest($url);
 * 或
 * $url = 'http://a.com/get.php';
 * $param = array('c'=>1,'b'=>2);
 * echo Http::sendRequest($url,$param);
 *
 * 2:以POST方式请求 http://a.com/get.php
 * $url = 'http://a.com/get.php';
 * $param = array('c'=>1,'b'=>2);
 * echo Http::sendRequest($url,$param,'POST');
 *
 * // 发送404找不到页面的HTTP头信息
 * Http::sendStatus(404);
 *
 * // HTTP AUTH USER 请求,弹出浏览器系统 对话框进行身份认证
 * $user = Http::getAuthUser();
 * if(empty($user) || ($user['user'] != 'dev' || $user['pwd'] != 'wx'))
 * {
 *     Http::sendAuthUser('deving, need the authentication.','input error, deving, need the authentication.');
 * }
 *
 */
class Http
{
    /**
     * 阻止浏览器刷新，但可以CTRL+F5强制刷新
     *
     * @param int $seconds 防刷新的时间
     */
    public static function limitRefresh($seconds = 5)
    {
        $cookie_name   = 'limitRefresh_' . md5(serverv('PHP_SELF'));
        $cache_control = serverv('HTTP_CACHE_CONTROL');
        if (isset($_COOKIE[$cookie_name]) && ($_COOKIE[$cookie_name] + $seconds > time()) && $cache_control != 'no-cache') {
            header('HTTP/1.1 304 Not Modified');
            exit;
        } else {
            $expire = serverv('REQUEST_TIME', 0) + $seconds;
            setcookie($cookie_name, serverv('REQUEST_TIME'), $expire + 3600, '/', serverv('SERVER_NAME'));
            header('Last-Modified: ' . date('D,d M Y H:i:s', $expire) . ' GMT');
            header('Expires: ' . date('D,d M Y H:i:s', $expire) . ' GMT');
        }
    }

    /** Forces the user's browser not to cache the results of the current request. **/
    public static function disableBrowserCache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * 下载文件
     *
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容
     *
     * @param string $filename 下载文件名,要是绝对路径
     * @param string $showname 下载时显示的文件名,默认为下载的文件名
     * @param string $content 下载的内容
     * @return void
     * @throws CommonException
     */
    public static function download($filename = '', $showname = '', $content = '')
    {
        // 得到下载长度
        if (file_exists($filename)) {
            $length = filesize($filename);
        } elseif ($content != '') {
            $length = strlen($content);
        } else {
            throw new CommonException('Not DownLoad Load File !');
        }

        // 最到显示的下载文件名
        if ($showname == '') {
            $showname = $filename;
        }

        $showname = basename($showname);

        // 发送Http Header信息 开始下载
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . $length);
        header("Content-Disposition: attachment; filename=" . $showname);

        // 优先下载指定的内容再下载文件
        if ($content == '') {
            $file = @fopen($filename, "r");
            if (!$file) {
                throw new CommonException('Not DownLoad Load File !');
            }
            // 一次读一K内容
            while (!@feof($file)) {
                echo @fread($file, 1024 * 1000);
            }

            @fclose($file);
        } else {
            exit($content);
        }
    }

    /**
     * 向客户端发送http status
     *
     * @param $code
     */
    public static function sendStatus($code)
    {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found', // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        );
        if (array_key_exists($code, $_status)) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        }
    }

    /**
     * 发送 HTTP AUTH USER 请求
     *
     * 使其弹出一个用户名／密码输入窗口。当用户输入用户名和密码后,脚本将会被再次调用.
     * 这时就可以调用 Http::getAuthUser()方法得到输入的用户名和密码了
     *
     * @param string $hintMsg 认证弹出框提示语
     * @param string $errorMsg 认证失败提示语
     */
    public static function sendAuthUser($hintMsg, $errorMsg = '')
    {
        header("WWW-Authenticate: Basic realm=\"{$hintMsg}\"");
        header('HTTP/1.0 401 Unauthorized');
        exit($errorMsg);
    }

    /**
     * 得到 HTTP AUTH USER 请求后的用户名和密码
     *
     *  - 如果没有发送该请求该会返回 false,否则返回包含用户名和密码的数组，格式如下:
     *  - array('user'=>'yuanwei',
     *       'pwd'=>'123456');
     *
     * @return mixed|array
     */
    public static function getAuthUser()
    {
        if (serverv('PHP_AUTH_USER')) {
            return array('user' => serverv('PHP_AUTH_USER'), 'pwd' => serverv('PHP_AUTH_PW'));
        } else {
            return false;
        }
    }

    // 获取客户端IP地址
    public static function clientIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $ip = getenv("REMOTE_ADDR");
        } elseif (strcasecmp(serverv('REMOTE_ADDR'), "unknown")) {
            $ip = serverv('REMOTE_ADDR');
        } else {
            $ip = "unknown";
        }

        return $ip;
    }

    /*
     * 将ip v4地址转换为 所在地区
     *
     * @param string $ip 要转换ip的地址
     */
    public static function clientArea($ip = '')
    {
        if (!$ip) {
            $ip = self::clientIp();
        }

        return json_decode(file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip), true);
    }

    // 设置页面缓存,使表单在返回时不清空
    public static function setFormCache()
    {
        session_cache_limiter('private,must-revalide');
    }

    /**
     * 发送 HTTP 请求,支持 GET POST 方式,推荐系统中安装 CURL 扩展库
     *  - 返回请求后的页面内容
     *
     * @param string $url : 请求的url地址
     * @param mixed $params : GET或POST的参数,如 array('id'=>1,'name'=>'yuanwei')或者可以能是一个entry的xml。
     * @param string $method : 请求方式 GET POST
     * @param array $header 发送http header信息
     * @param integer $timeout 超时秒数
     * @return string
     */
    public static function sendRequest($url, $params = array(), $method = 'GET', $header = array(), $timeout = 0)
    {
        // 如果安装了 curl 库则优先使用它
        if (function_exists('curl_init')) {
            $ch = curl_init();
            if ($method == 'GET') {
                if (strpos($url, '?')) {
                    $url .= '&' . http_build_query($params);
                } else {
                    $url .= '?' . http_build_query($params);
                }

                curl_setopt($ch, CURLOPT_URL, $url);
            } elseif ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                $post_data = is_array($params) ? http_build_query($params) : $params;
                // $post_data = $params; // 如果用来发送文件，文件名前加@，CURLOPT_POSTFIELDS必须为数组。
                // （如果value是一个数组，Content-Type头将会被设置成multipart/form-data。）

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            #send header
            if (!empty($header)) {
                //curl_setopt($ch, CURLOPT_NOBODY,FALSE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            }

            // case 'DELETE':
            // curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
            // curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
            // curl_setopt ($ch, CURLOPT_REFERER, 'http://xxxxx/xx.php' );

            // 在启用CURLOPT_RETURNTRANSFER的时候，返回原生的（Raw）输出。
            // curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

            if ($timeout) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            }
            // 超时时间
            return curl_exec($ch);
        } else {
            $data_string = http_build_query($params);
            $context     = ['http' => [
                'method'  => $method,
                'header'  => 'Content-type: application/x-www-form-urlencoded' . "\r\n" . 'Content-length: ' . strlen($data_string),
                'content' => $data_string
            ]];

            $context_id = stream_context_create($context);
            $sock      = fopen($url, 'r', false, $context_id);
            $result    = '';
            if ($sock) {
                while (!feof($sock)) {
                    $result .= fgets($sock, 4096);
                }

                fclose($sock);
            }
            return $result;
        }
    }
}

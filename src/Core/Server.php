<?php
namespace ePHP\Core;

class Server
{
    /**
     * Static file content type
     *
     * @var array
     */
    private $contentType = [
        'html' => 'text/html',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'ico'  => 'image/x-icon',
        'css'  => 'text/css',
        'js'   => 'text/js'
    ];

    /**
     * ePHP latest verson
     *
     * @var string
     */
    private $version = '7.2.0';

    /**
     * Handle of Swoole http server
     *
     * @var swoole_http_server
     */
    public $server;

    /**
     * @var \ePHP\Core\Server
     */
    private static $instance;

    /**
     * Dynamically handle calls to the class.
     *
     * @return \ePHP\Core\Server
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            return self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Print server finger description
     *
     * @param  string $bind_http    eg: 127.0.0.1:8000
     * @param  bool   $is_swoole    Whether swoole server
     * @return null
     */
    private function printServerFinger(string $bind_http, bool $is_swoole)
    {
        $version       = $this->version;
        $software      = $is_swoole ? 'Swoole Server' : 'PHP Development Server';
        $document_root = APP_PATH . '/public';

        echo <<<EOT
-----------------------------------\033[32m
      ____    __  __  ____
     /\  _`\ /\ \/\ \/\  _`\
   __\ \ \ \ \ \ \_\ \ \ \ \ \
 /'__`\ \ ,__/\ \  _  \ \ ,__/
/\  __/\ \ \   \ \ \ \ \ \ \
\ \____ \\ \_\   \ \_\ \_\ \_\
 \/____/ \/_/    \/_/\/_/\/_/ \033[43;37mv{$version}\033[0m
 \033[0m
>>> \033[35m{$software}\033[0m started ...
Listening on \033[36;4mhttp://{$bind_http}/\033[0m
Document root is \033[34m{$document_root}\033[0m
Press Ctrl-C to quit.
-----------------------------------

EOT;
    }

    /**
     * Start a PHP Development Server
     *
     * @param  string $host
     * @param  int    $port
     * @return null
     */
    function devServer(string $host, int $port)
    {
        // Mark server mode
        define('SERVER_MODE', 'buildin');

        $bind_http = $host . ':' . $port;
        $this->printServerFinger($bind_http, false);

        // Start cli Development Server
        passthru("php -S {$bind_http} -t " . APP_PATH . "/public");
    }

    /**
     * Create a swoole server
     *
     * @param  array $config server config
     * @return \swoole_http_server
     */
    function createServer(array $config)
    {
        // Mark server mode
        define('SERVER_MODE', 'swoole');

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '8000';

        $this->server = new \swoole_http_server($host, $port);
        $this->server->set($config);

        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);

        return $this;
    }

    /**
     * Start swoole server
     *
     * @return null
     */
    function start()
    {
        // Linsten http Event
        $this->server->on('request', [$this, 'onRequest']);

        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('shutdown', [$this, 'onShutdown']);

        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('workerStop', [$this, 'onWorkerStop']);
        $this->server->on('workerError', [$this, 'onWorkerError']);

        // Start a new http server
        $this->server->start();
    }

    function stop()
    {
    }

    function reload()
    {
    }

    /**
     * Linsten http server onRequest
     *
     * @param  swoole_http_request  $request
     * @param  swoole_http_response $response
     */
    function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        // Override php globals array
        // Store $_GET, $_POST ....
        $_GET    = $request->get ?? [];
        $_POST   = $request->post ?? [];
        $_COOKIE = $request->cookie ?? [];
        $_FILES  = $request->files ?? [];
        $_REQUEST  = array_merge($_COOKIE, $_GET, $_POST);

        // 注入全局变量
        $GLOBALS['__$request']  = $request;
        $GLOBALS['__$response'] = $response;
        $GLOBALS['__$DB_QUERY_COUNT'] = 0;

        // 兼容php-fpm的$_SERVER
        $_SERVER = [];
        foreach ($request->server as $key => $value) {
            $key = strtoupper($key);
            $_SERVER[$key] = $value;

            // FIXED: swoole REQUEST_URI don't contains QUERY_STRING
            if ($key === 'REQUEST_URI' && isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        // 兼容php-fpm的header传值
        foreach ($request->header as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $_SERVER[$key] = $value;
            } else {
                $_SERVER['HTTP_' . $key] = $value;
            }
        }

        $response->header('Server', 'ePHP/'. $this->version);

        $filename = APP_PATH . '/public'. $_SERVER['PATH_INFO'];
        $extname = substr($filename, strrpos($filename, '.')+1);

        if (!( $extname === 'html'
                || $extname === 'css'
                || $extname === 'js'
                || $extname === 'png'
                || $extname === 'jpg'
                || $extname === 'gif'
                || $extname === 'ico')
        ) {
            ob_start();
            (new \ePHP\Core\Application())->run();
            $h = ob_get_clean();

            // echo "----------------------\n". $h ."\n";

            // Fixed output o byte
            if (strlen($h) === 0) {
                $h = ' ';
            }

            $response->end($h);
        } else {
            if (is_file($filename)) {
                $response->header('Content-Type', $this->contentType[$extname]);
                $response->sendfile($filename);
                // if (PHP_OS !== 'Darwin') {
                //     $response->sendfile($filename);
                // } else {
                //     $response->end(file_get_contents($filename));
                // }
            } else {
                // 都匹配不到，展示404界面
                ob_start();
                $tpl = Config::get('tpl_404');
                if (!$tpl) {
                    include __DIR__ . '/../Template/404.html';
                } else {
                    include APP_PATH . '/views/' . $tpl;
                }
                $h = ob_get_clean();

                $response->end($h);
            }
        }
    }

    function onStart(\swoole_http_server $server)
    {
        $bind_http = $server->setting['host'] . ':' . $server->setting['port'];
        $this->printServerFinger($bind_http, true);
    }

    function onShutdown(\swoole_http_server $server)
    {
        echo "http server shutdown ...... \n";
    }

    function onWorkerStart(\swoole_http_server $server, int $worker_id)
    {
        // echo "http worker start ...... \n";
    }

    function onWorkerStop(\swoole_http_server $server, int $worker_id)
    {
        echo "http worker stop[{$worker_id}] ...... \n";
    }

    function onWorkerError(\swoole_http_server $server, int $worker_id, int $worker_pid, int $exit_code)
    {
        echo "http worker error[{$worker_id}] ...... \n";
    }

    function onTask(\swoole_server $serv, $task_id, $from_id, $data)
    {
    }

    function onFinish(\swoole_http_server $serv, $task_id, $data)
    {
    }
}

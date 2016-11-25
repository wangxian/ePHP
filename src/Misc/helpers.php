<?php

use ePHP\Core\Config;

/**
 * 打印，调试方法
 *
 * 使用Chrome/Firefox JavaScript的 `console.info` 方法打印服务器端信息
 * 默认使用console.info打印信息
 * 使用方法：dump('当前变量', $your_vars1, $your_vars2)
 * 进阶：可使用dump('error', 错误信息)
 *
 * @param mixed $args
 * @return void
 */
function dump()
{
    $args         = func_get_args();
    $console_func = func_get_arg(0);

    if (count($args) > 1 && in_array($console_func, ['log', 'info', 'error']))
    {
        array_shift($args);
    }
    else
    {
        $console_func = 'info';
    }

    echo '<script type="text/javascript">if(!!window.console) console.' . $console_func . '.apply(null, ' . json_encode($args) . ');</script>';
}

/**
 * 等同于dump();exit;
 *
 * @return void
 */
function dumpdie()
{
    dump(func_get_args());exit;
}

/**
 * 系统异常处理函数，将系统异常，重定向到CommonException去处理
 *
 * @ignore
 * @return void
 *
 * @throws throw \ePHP\Exception\CommonException
 */
function error_handler($errno, $errstr, $errfile, $errline)
{
    throw new \ePHP\Exception\CommonException($errstr, $errno, array('errfile' => $errfile, 'errline' => $errline));
}

// 捕获系统所有的异常
set_error_handler("error_handler");

// db query cout
// 记录数据库查询执行次数，这也是一个优化的手段
// 用在run_info方法中
$_SERVER['run_dbquery_count'] = 0;

function run_info($verbose = false)
{
    dump('当前系统运行耗时:', number_format((microtime(1) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2, '.', ''), 'ms');
    if ($verbose)
    {
        dump('当前数据库查询次数:', $_SERVER['run_dbquery_count']);
    }
}

// 允许自定义写日志方法
if (!function_exists('wlog'))
{
    /**
     * 写文件日志
     *
     * @param string $name 日志名称，自动加上{2010-09-22.log}的作为文件名
     * @param string $value
     * @return void
     */
    function wlog($name, $value)
    {
        $logdir = APP_PATH . '/' . Config::get('log_dir');
        if (!is_writeable($logdir))
        {
            exit('ERROR: Log directory {' . $logdir . '} is not writeable, check the directory permissions!');
        }

        // 修复：跑在toolbox docker-machine下，文件权限问题，
        // 文件创建后，以后不能再次写入，不然报错
        $filename = $logdir . $name . date('Y-m-d') . '.log';
        if (file_exists($filename) && !is_writeable($filename))
        {
            exit('ERROR: {' . $filename . '} is not writeable, check the file permissions!');
        }

        error_log('[' . date('H:i:s') . ']' . $value . "\n", 3, $logdir . $name . date('Y-m-d') . '.log');
    }
}

/**
 * 显示404页面
 *
 * @return void
 */
function show_404()
{
    // header('HTTP/1.1 404 Not Found');
    $tpl = Config::get('tpl_404');
    if (!$tpl)
    {
        include __DIR__ . '/../Template/404.html';
    }
    else
    {
        include APP_PATH . '/views/' . $tpl;
    }
    exit;
}

/**
 * 显示一个成功的界面，几秒后，跳转到上一个界面
 *
 * 例如：show_success("操作成功！")
 * show_error("操作成功", "/user/index", 3)
 *
 * @param string  $message 要显示的消息内容
 * @param string  $url     可选，要跳转的URL，如果省略则使用referer，跳转到上一个界面
 * @param int $wait        可选，自动跳转等待时间，默认6s
 * @return void
 */
function show_success($message, $url = '', $wait = 6)
{
    if ($url === '' && isset($_SERVER['HTTP_REFERER']))
    {
        $url = $_SERVER['HTTP_REFERER'];
    }

    $tpl = Config::get('tpl_success');
    if (!$tpl)
    {
        include __DIR__ . '/../Template/200.html';
    }
    else
    {
        include APP_PATH . '/views/' . $tpl;
    }

    exit;
}

/**
 * 显示一个错误信息，几秒后跳转到上一个界面
 *
 * 例如：show_error("抱歉，操作失败！")
 * show_error("抱歉，操作失败", "/user/index", 3)
 *
 * @param string  $message 要显示的消息内容
 * @param string  $url     可选，要跳转的URL，如果省略则使用referer，跳转到上一个界面
 * @param int     $wait    可选，自动跳转等待时间，默认6s
 * @return void
 */
function show_error($message, $url = '', $wait = 6)
{
    // header('HTTP/1.1 500 Internal Server Error');
    if ($url === '' && isset($_SERVER['HTTP_REFERER']))
    {
        $url = $_SERVER['HTTP_REFERER'];
    }

    $tpl = Config::get('tpl_error');
    if (!$tpl)
    {
        include __DIR__ . '/../Template/500.html';
    }
    else
    {
        include APP_PATH . '/views/' . $tpl;
    }

    exit;
}

/**
 * 浏览器跳转
 *
 * @param string  $url     要跳转的url
 * @param int     $wait    可选，跳转等待时间，默认0s
 * @param string  $message 可选，提示信息
 */
function R($url, $wait = 0, $message = '')
{
    // header("HTTP/1.1 301 Moved Permanently");
    if (empty($message))
    {
        $message = "系统将在{$wait}秒之后自动跳转到{$url}！";
    }

    if (!headers_sent() && (0 === $wait))
    {
        // redirect
        header("Content-Type:text/html; charset=UTF-8");
        header("Location: {$url}");
        exit;
    }
    else
    {
        // html refresh
        // header("refresh:{$wait};url={$url}"); // 直接发送header头。
        include __DIR__ . '/../Template/302.html';
        exit;
    }
}

/**
 * 获取$_GET中的值，不存在，返回default的值
 *
 * @param string $key      要获取的键名
 * @param string $default  可选，如果不存在返回的默认值，默认返回空字符串
 * @param string $callback 可选，回调函数，比如 intval, floatval
 * @return mixed
 */
function getv($key, $default = '', $callback = '')
{
    return isset($_GET[$key]) ? (empty($callback) ? trim($_GET[$key]) : call_user_func($callback, trim($_GET[$key]))) : $default;
}

/**
 * 获取url中的片段
 *
 * 例如：url: /user/info/12.html, getp(3)的值为12
 *
 * @param int    $pos      获取url片段的位置($pos>=1)
 * @param string $default  可选，返回的默认值
 * @param string $callback 可选，回调函数，比如 intval, floatval
 * @return mixed
 */
function getp($pos, $default = '', $callback = '')
{
    static $url_part = array();
    if (empty($url_part))
    {
        // only first time
        $posi = strpos($_SERVER['PATH_INFO'], '?');
        $url  = $posi ? substr($_SERVER['PATH_INFO'], 1, $posi) : substr($_SERVER['PATH_INFO'], 1);
        if (!empty($url))
        {
            $url_part = explode('/', $url);
        }
        else
        {
            $url_part = array('index', 'index');
        }

    }
    $pos = $pos - 1;
    return isset($url_part[$pos]) ? (empty($callback) ? trim($url_part[$pos]) : call_user_func($callback, trim($url_part[$pos]))) : $default;
}

/**
 * 获取$_POST中的值
 *
 * @param string $key      要获取的键名
 * @param string $default  可选，如果不存在返回的默认值，默认返回空字符串
 * @param string $callback 可选，回调函数，比如 intval, floatval
 * @return mixed
 */
function postv($key, $default = '', $callback = '')
{
    return isset($_POST[$key]) ? (empty($callback) ? trim($_POST[$key]) : call_user_func($callback, trim($_POST[$key]))) : $default;
}

/**
 * 获取$_REQUEST中的值
 *
 * @param string $key      要获取的键名
 * @param string $default  可选，如果不存在返回的默认值，默认返回空字符串
 * @param string $callback 可选，回调函数，比如 intval, floatval
 * @return mixed
 */
function requestv($key, $default = '', $callback = '')
{
    return isset($_REQUEST[$key]) ? (empty($callback) ? trim($_REQUEST[$key]) : call_user_func($callback, trim($_REQUEST[$key]))) : $default;
}

/**
 * 获取配置信息
 *
 * @param  string $key
 * @param  string $config_name 配置项名称，如mian
 * @return mixed
 */
function C($key, $config_name = 'main')
{
    return \ePHP\Core\Config::get($key, $config_name);
}

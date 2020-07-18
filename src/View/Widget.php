<?php /** @noinspection PhpUnused */
/** @noinspection PhpUndefinedConstantInspection */

/**
+------------------------------------------------------------------------------
 * 挂件类
 * Widget for ePHP
+------------------------------------------------------------------------------
 * @version 7.2
 * @author WangXian
 * @email wo#wangxian.me
 * @package  \ePHP\View
 * @creation_date 2011-06-04
 * @last_modified 2016-12-03
+------------------------------------------------------------------------------
 */

namespace ePHP\View;

class Widget
{
    // 模板变量assign
    private $tVar = array();

    // abstract public function run();
    /**
     * @var Widget
     */
    private $view;

    public function __construct()
    {
        // 不改变控制器中的使用习惯
        $this->view = $this;
    }

    /**
     * @param string $name widget name
     * @param string $data default ''
     * @noinspection PhpIncludeInspection
     */
    public static function show($name, $data = '')
    {
        $classname = $name . 'Widget';
        include APP_PATH . '/widgets/' . $name . '/' . $classname . '.php';

        $widget = new $classname;
        if (method_exists($widget, 'run')) {
            $widget->run($data);
        } else {
            throw_error("{$classname} 中run()接口方法未定义.", 110605);
        }
    }

    /**
     * widget视图渲染
     *
     * @param string $file
     */
    protected function render($file = '')
    {
        if (!$file) {
            $file = substr(get_class($this), 0, -6);
        }

        $filename = APP_PATH . '/widgets/' . $file . '/' . $file . '.tpl.php'; //视图全路径

        if (!file_exists($filename)) {
            throw_error("widget模版文件：{$filename} 不存在，请检查以确认。");
        }

        if (!empty($this->tVar)) {
            extract($this->tVar);
        }

        // 释放assign的变量
        /** @noinspection PhpIncludeInspection */
        include $filename;
    }

    protected function layout($file = '')
    {
        $this->render($file);
    }

    protected function assign($name, $value = '')
    {
        $this->tVar[$name] = $value;
    }
}

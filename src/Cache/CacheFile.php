<?php /** @noinspection ALL */

namespace ePHP\Cache;

use ePHP\Core\Config;
use ePHP\Exception\ExitException;

class CacheFile
{
    /**
     * 获取缓存
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $filename = $this->_filename($key);
        if (!file_exists($filename)) {
            return false;
        }

        $tmp_value = file_get_contents($filename);
        $expire    = (int)substr($tmp_value, 13, 24);

        // echo time()."<br />\n";
        // echo $expire."<br />\n";
        // echo substr($tmp_value,23);exit;

        if ($expire != 0 && time() < $expire) {
            return unserialize(substr($tmp_value, 23));
        } else {
            return false;
        }
    }

    /**
     * 写缓存
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire 有效期，0,长期有效, -1: 不缓存，1：缓存
     * @return int
     */
    public function set($key, $value, $expire = 0)
    {
        // 如果expire为0时，设为长期有效。
        if ($expire != 0) {
            $expire = time() + $expire;
        } else {
            $expire = time() * 2;
        }

        $value = '<?php exit;?>' . $expire . serialize($value);

        // 检查目录可写否
        /** @noinspection PhpUndefinedConstantInspection */
        $cacheDir = APP_PATH . '/' . Config::get("cache_dir");
        if (!is_writeable($cacheDir)) {
            \show_error('ERROR: ' . $cacheDir . ' is not writeable!');
        }

        return file_put_contents($this->_filename($key), $value);
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return unlink($this->_filename($key));
    }

    /**
     * 清理所有的缓存
     *
     * @param string $dir 删除指定目录的缓存
     * @return void
     */
    public function flush($dir = '')
    {
        /** @noinspection PhpUndefinedConstantInspection */
        $dir = $cache_dir = APP_PATH . '/' . Config::get('cache_dir');
        \ePHP\Misc\Dir::deleteDir($dir);
        mkdir($dir, 0777);
    }

    /**
     * 计算缓存名称
     *
     * @param string $key
     * @return string
     * @access private
     * @noinspection PhpUndefinedConstantInspection
     */
    private function _filename($key)
    {
        if (true == ($dir_pos = strrpos($key, '/'))) {
            // 有子目录，也可能有多层子目录。
            $cache_name = substr($key, $dir_pos + 1);

            // 缓存目录
            $cache_dir = APP_PATH . '/' . Config::get('cache_dir') . substr($key, 0, $dir_pos) . '/';

            // 递归创建文件夹
            if (!is_dir($cache_dir)) {
                mkdir($cache_dir, 0777, true);
            }
        } else {
            // 无子目录
            $cache_name = $key;
            $cache_dir  = APP_PATH . '/' . Config::get('cache_dir');
        }

        // 缓存文件名
        return $cache_dir . trim($cache_name) . '^' . md5($cache_name) . '.cache';
    }
}

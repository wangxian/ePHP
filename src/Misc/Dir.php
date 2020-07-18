<?php
/** @noinspection ALL */

// 目录操作类
namespace ePHP\Misc;

class Dir
{
    /**
     * 遍历目录
     *
     * 默认获取相对于index.php目录下的目录和文件
     *
     * @param string $source_dir 相对于index.php的目录
     * @param boolean $subdir 递归子目录,true包含，false不包含
     * @return mixed
     */
    public static function map($source_dir, $subdir = true)
    {
        if (true == ($fp = opendir($source_dir))) {
            $source_dir = rtrim($source_dir, '/') . '/';
            $filedata   = array();

            while (false !== ($file = readdir($fp))) {
                if ($file == '.' or $file == '..') {
                    continue;
                }

                if ($subdir && is_dir($source_dir . $file)) {
                    $temp_array      = array();
                    $temp_array      = self::map($source_dir . $file . '/', $subdir);
                    $filedata[$file] = $temp_array;
                } else {
                    $filedata[] = $file;
                }
            }

            closedir($fp);
            return $filedata;
        } else {
            return false;
        }
    }

    /**
     * 判断目录是否为空
     *
     * @param string $dir
     * @return boolean true为空，false不为空
     */
    public static function isEmpty($dir)
    {
        $handle = opendir($dir);
        $i      = 0;
        while (false !== ($file = readdir($handle))) {
            $i++;
        }

        closedir($handle);

        if ($i >= 2) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 删除目录以及子目录的内容
     *
     * @param string $dir
     * @return boolean
     */
    public static function deleteDir($dir)
    {
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $currele = $d->path . '/' . $entry;
            if (is_dir($currele)) {
                if (self::isEmpty($currele)) {
                    rmdir($currele);
                } else {
                    self::deleteDir($currele);
                }
            } else {
                unlink($currele);
            }
        }
        $d->close();

        rmdir($dir);
        return true;
    }
}

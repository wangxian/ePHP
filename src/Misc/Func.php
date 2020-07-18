<?php /** @noinspection PhpUnused */

namespace ePHP\Misc;

/**
 * 常用函数类
 *
 * <code>
 * echo Func::auto_charset('我是中国人','utf-8','gbk');//自动编码转换
 * echo Func::randNum();//返回一个随机的整数
 * echo Func::randNum(1,100);//返回1-100之间的整数
 * echo Func::md5Rand();//返回永远唯一32位md5的随机数
 * echo Func::msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true);//字符串截取，支持中文和其他编码
 * echo Func::randString();//得到默认长度6位,字母和数字混合的随机字串
 * echo Func::toHtml('<a href="a.php">aaaa</a>');//输出对应的HTML字符
 * echo Func::strExists('34','12345');//判断'34'是否出现在'1234'中
 * echo Func::byte_format(大小，保留几位小数) 格式化文件大小
 * echo Func::is_utf8()
 * echo Func::highlight_code()
 * echo Func::h(); //输出安全的html
 * echo Func::ubb();
 * echo Func::remove_xss() //过滤xss攻击
 * </code>
 */
class Func
{
    /**
     * 自动转换字符集 支持数组转换
     *
     * @param string $fContents
     * @param string $from 编码
     * @param string $to 转换后
     * @return string
     */
    public static function auto_charset($fContents, $from, $to)
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to   = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            // 如果编码相同或者非字符串标量则不转换
            return $fContents;
        }

        if (is_string($fContents)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($fContents, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $fContents);
            } else {
                return $fContents;
            }

        } elseif (is_array($fContents)) {
            foreach ($fContents as $key => $val) {
                $_key             = self::auto_charset($key, $from, $to);
                $fContents[$_key] = self::auto_charset($val, $from, $to);
                if ($key != $_key) {
                    unset($fContents[$key]);
                }
            }
            return $fContents;
        } else {
            return $fContents;
        }
    }

    /**
     * 产生一个随机数
     *
     * @param int $min 最小
     * @param int $max 最大
     * @return integer
     */
    public static function randNum($min = null, $max = null)
    {
        mt_srand((double)microtime() * 1000000);
        if ($min === null || $max === null) {
            return mt_rand();
        } else {
            return mt_rand($min, $max);
        }
    }

    /**
     * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
     *
     * @param int $len 长度
     * @param int $type 0大小写字母，1数字，2大写字母，3小写字母，4中文,5大小写数字
     * @param string $addChars 额外字符
     * @return string
     */
    public static function randString($len = 6, $type = 3, $addChars = '')
    {
        $str   = '';
        $chars = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借" . $addChars;
                break;
            case 5:
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
            // default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            //                $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            //                break;
        }

        if ($len > 10) {
            // 位数过长重复字符串一定次数
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }

        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str   = substr($chars, 0, $len);
        } else {
            // 中文随机字
            for ($i = 0; $i < $len; $i++) {
                $str .= self::msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8', false);
            }
        }
        return $str;
    }

    /**
     * 返回永远唯一32位md5的随机数
     *
     * @return string
     */
    public static function md5Rand()
    {
        srand((double)microtime() * 1000000);
        return md5(uniqid(time() . rand()));
    }

    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param bool $suffix 截断显示字符,是否显示 '...'
     * @return string
     */
    public static function msubstr($str, $start, $length, $charset = "UTF-8", $suffix = false)
    {
        if (function_exists("mb_substr")) {
            $slice = mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        if ($suffix) {
            return $slice . "…";
        }

        return $slice;
    }

    /**
     * 字节格式化 把字节数格式为   描述的大小
     *
     * @param mixed $size
     * @param integer $dec 保留几位小数
     * @return string
     */
    public static function byte_format($size, int $dec = 2)
    {
        $a   = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, $dec) . " " . $a[$pos];
    }

    /**
     * 检查字符串是否是UTF8编码
     *
     * @param string $string
     * @return false|int
     */
    public static function is_utf8(string $string)
    {
        return preg_match('%^(?:
             [\x09\x0A\x0D\x20-\x7E]            # ASCII
           | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
           |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
           | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
           |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
           |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
           | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
           |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
       )*$%xs', $string);
    }

    /**
     * 代码加亮
     *
     * @param string $str 要高亮显示的字符串 或者 文件名
     * @param boolean $show 是否输出
     * @return string
     * @noinspection RegExpRedundantEscape
     */
    public static function highlight_code($str, $show = false)
    {
        if (file_exists($str)) {
            $str = file_get_contents($str);
        }

        $str = stripslashes(trim($str));

        $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);
        $str = str_replace(array('&lt;?php', '?&gt;', '\\'), array('phptagopen', 'phptagclose', 'backslashtmp'), $str);

        $str = '<?php //tempstart' . "\n" . $str . '//tempend ?>';                                                                                                                                                                                                             // <?

        // All the magic happens here, baby!
        $str = highlight_string($str, true);

        if (abs(phpversion()) < 5) {
            $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
            $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
        }

        // Remove our artificially added PHP
        $str = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $str);
        $str = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $str);
        $str = preg_replace("#//tempend.+#is", "</span>\n</code>", $str);

        // Replace our markers back to PHP tags.
        $str    = str_replace(array('phptagopen', 'phptagclose', 'backslashtmp'), array('&lt;?php', '?&gt;', '\\'), $str);                                                                                                                                                        //<?
        $line = explode("<br />", rtrim(ltrim($str, '<code>'), '</code>'));
        $result = '<div class="code"><ol>';
        foreach ($line as $key => $val) {
            $result .= '<li>' . $val . '</li>';
        }

        $result .= '</ol></div>';
        $result = str_replace("\n", "", $result);

        if ($show !== false) {
            echo $result;
            return null;
        } else {
            return $result;
        }
    }

    /**
     * 输出安全的html
     *
     * @param string $text
     * @param string $tags ,不允许的html标签。
     * @return string|string[]
     * @noinspection RegExpRedundantEscape
     * @noinspection RegExpSingleCharAlternation
     */
    public static function h(string $text, string $tags = null)
    {
        $text = trim($text);
        $text = preg_replace('/<!--?.*-->/', '', $text); //完全过滤注释

        // 完全过滤动态代码
        $text = preg_replace('/<\?|\?' . '>/', '', $text);

        // 完全过滤js
        $text = preg_replace('/<script?.*\/script>/', '', $text);

        $text = str_replace('[', '&#091;', $text);
        $text = str_replace(']', '&#093;', $text);
        $text = str_replace('|', '&#124;', $text);

        // 过滤换行符
        $text = preg_replace('/\r?\n/', '', $text);
        //br
        $text = preg_replace('/<br(\s\/)?' . '>/i', '[br]', $text);
        $text = preg_replace('/(\[br\]\s*){10,}/i', '[br]', $text);

        // 过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1], $text);
        }

        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1] . $mat[3], $text);
        }

        if (empty($tags)) {
            $tags = 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a';
        }

        // 允许的HTML标签
        $text = preg_replace('/<(' . $tags . ')( [^><\[\]]*)>/i', '[\1\2]', $text);
        //过滤多余html
        $text = preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml)[^><]*>/i', '', $text);

        // 过滤合法的html标签
        while (preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i', $text, $mat)) {
            $text = str_replace($mat[0], str_replace('>', ']', str_replace('<', '[', $mat[0])), $text);
        }

        // 转换引号
        while (preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4], $text);
        }

        // 过滤错误的单个引号
        while (preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i', $text, $mat)) {
            $text = str_replace($mat[0], str_replace($mat[1], '', $mat[0]), $text);
        }

        // 转换其它所有不合法的 < >
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);
        $text = str_replace('"', '&quot;', $text);

        // 反转换
        $text = str_replace('[', '<', $text);
        $text = str_replace(']', '>', $text);
        $text = str_replace('|', '"', $text);

        // 过滤多余空格
        $text = str_replace('  ', ' ', $text);
        return $text;
    }

    /**
     * 过滤xss数据
     *
     * @param string $val 要过滤的字符串
     * @return string
     */
    public static function remove_xss($val)
    {
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c\x0e-\x19])/', '', $val);

        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);              // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra  = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern     .= '/i';
                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
                $val         = preg_replace($pattern, $replacement, $val);         // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }

    /**
     * 判断指定的字符串是否存在
     * 例子: $str='34' $string='1234' 返回 TRUE
     *
     * @param string $str :字符或字符串(子串)
     * @param string $string :字符串(母串)
     * @return bool
     */
    public static function strExists(string $str, string $string)
    {
        $string = (string)$string;
        $str    = (string)$str;
        return strstr($string, $str) === false ? false : true;
    }

    /**
     * 转换 HTML 特殊字符以及空格和换行符
     *
     * 一般将 <textarea> 标记中输入的内容从数据库中读出来后在网页中显示
     *
     * @param string $text
     * @return string
     */
    public static function toHtml(string $text)
    {
        $text = htmlspecialchars($text);
        $text = nl2br(str_replace(' ', '&nbsp;', $text));
        return $text; //define in QuickPHP\Libs\CoreFunction.inc.php
    }

    /**
     * 安全base64_encode
     *
     * 替换掉+ / = 字符，这样不用urldecode了
     *
     * @param $string
     * @return string
     */
    public static function safe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    /**
     * 安全的base64_encode
     *
     * @param $string
     * @return string
     */
    public static function safe_b64decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}

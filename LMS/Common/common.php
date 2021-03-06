<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/9 0009
 * Time: 上午 10:04
 */
/***
 * 获取一个随机码。
 * @return string
 */
function get_uniqid(){
    return sha1(C('CSSF_STRING').time().'sdoi1io');
}

/**
 * 返回session口令
 * @return string
 */
function get_session_key(){
    return sha1(C('CSSF_STRING').$_SERVER['USER_AGENT'].session_id().'slidf');
}

/**
 * 用户登出
 */
function logout(){
    cookie('user_key',null);
    cookie('username',null);
    cookie('uid',null);
    session_unset();
    return ;
}

/**
 * 判断是否登录！
 * @return bool
 */
function is_login(){
    if(session('is_login'))
        return true;
    else
        return false;
}
function is_admin(){
    if(session(C('ADMIN_TAG')))
        return true;
    else
        return false;
}

/**
 * 计算密码
 * @param $uid
 * @param $pas
 * @return string
 */
function calc_password($pas){
    return sha1(C('CSSF_STRING').$pas.'sfi120i@');
}

/**
 * 计算用户key
 * @param $uid
 * @param $username
 * @return string
 */
function calc_user_key($uid,$username){
    return sha1(C('CSSF_STRING').$uid.$username.'sli1o29d');
}

/**
 * 打印出该数据。
 * @param $str
 */
function p($str){
    echo "<pre>";
    print_r($str);
    echo "</pre>";
}

/**
 * 清理某控制器的表单标识码
 * @param null $control
 */
function clearUniqid($control=null){
    $control=empty($control)?GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME:$control;
    unset($_SESSION[$control . '_uniqid']);
}
/*初始化唯一标识码，在common控制器里有个相同的方法！*/
/*function initUniqid(&$obj,$control=null){
    $control=empty($control)?GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME:$control;
    $uniqid=get_uniqid();
    session($control.'_uniqid',$uniqid);


    $obj->url_uniqid=$uniqid;
    $obj->uniqid=$uniqid;

    //快捷生成表单的字符串。
    $obj->__UNIQID__="<input type='hidden' name='uniqid' value='{$obj->uniqid}'/>";
    return true;
}*/

/**
 * 检查表单唯一标识码
 * @param string        $uniqid
 * @param string|null   $control 加密源源控制器的名称
 * @return bool
 */
function checkFormUniqid($uniqid,$control=null){
    $control=empty($control)?GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME:$control;

    //自动清理缓存文件
    if(isset($_SESSION[$control.'_uniqid'])) {
        $cache = $_SESSION[$control.'_cache_clean'];
        $cache = dirname($cache).'/'.basename($cache);//避免出现因为../导致文件删除BUG
        //p($cache);
        if(strpos($cache,HTML_PATH) === 0)
            unlink($cache);
        //unset($_SESSION[$control . '_uniqid']);
        unset($_SESSION[$control . '_cache_clean']);
    }

    if($uniqid!=session($control.'_uniqid'))//解密传递的UNIQID之后，与session里的原数据作比较。
        return false;
    else
        return true;
}

/**
 * 检查URL唯一标识码
 * @param $uniqid
 * @return bool
 */
function checkUrlUniqid($uniqid,$control=null){
    return checkFormUniqid($uniqid,$control);
}
function encode($string){
    return authcode($string,'ENCODE',C('CSSF_STRING'));
}
function decode($string){
    return authcode($string,'DECODE',C('CSSF_STRING'));
}
/**
 *
 * @param string $string 原文或者密文
 * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
 * @param string $key 密钥
 * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
 * @example
 *  $a = authcode('abc', 'ENCODE', 'key');
 *  $b = authcode($a, 'DECODE', 'key'); // $b(abc)
 *
 *  $a = authcode('abc', 'ENCODE', 'key', 3600);
 *  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
 */
function authcode($string,$operation='DECODE',$key='',$expiry=0){
    $ckey_length=4;
    $key=md5($key ? $key:"kalvin.cn");
    $keya=md5(substr($key,0,16));
    $keyb=md5(substr($key,16,16));
    $keyc=$ckey_length ? ($operation=='DECODE' ? substr($string,0,$ckey_length):substr(md5(microtime()),-$ckey_length)):'';
    $cryptkey=$keya.md5($keya.$keyc);
    $key_length=strlen($cryptkey);
    $string=$operation=='DECODE' ? base64_decode(substr($string,$ckey_length)):sprintf('%010d',$expiry ? $expiry+time():0).substr(md5($string.$keyb),0,16).$string;
    $string_length=strlen($string);
    $result='';
    $box=range(0,255);
    $rndkey=array();
    for($i=0;$i<=255;$i++){
        $rndkey[$i]=ord($cryptkey[$i%$key_length]);
    }
    for($j=$i=0;$i<256;$i++){
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++){
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i]) ^ ($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='DECODE'){
        if((substr($result,0,10)==0||substr($result,0,10)-time()>0)&&substr($result,10,16)==substr(md5(substr($result,26).$keyb),0,16)){
            return substr($result,26);
        }else{
            return'';
        }
    }else{
        return $keyc.str_replace('=','',base64_encode($result));
    }
}

/**
 * 在一个日志文件后添加日志.
 * @param $filename string 文件名
 * @param $send_email bool 是否发送邮件
 * @param $message string 发送消息内容
 * @return bool
 */
function add_log($message,$send_email=false,$filename='fintal.error.log'){
    $route=C('MINE_LOG_PATH').$filename;

    //如果不存在则创建日志文件目录！
    if(!is_dir(C('LOG_PATH')))
        mkdir(C('LOG_PATH'),0777,true);

    if(!$fp=fopen($route,'a+'))
        return false;

    $message=date('Y-m-d h:i:s',time())."\t".APP_PATH.C('APP_GROUP_PATH').'/'.GROUP_NAME.'/'.MODULE_NAME.'   '.$message."\n";
    //给管理员发送邮件。
    if($send_email)
        addEmailTimeQueue(C('ADMIN_EMAIL'),'尊敬的管理员','网站错误报告',$message,time());
    fwrite($fp,$message);
    fclose($fp);
    return true;
}
/**
 * 用$filter对数组进行递归过滤！
 * @param $arr
 * @param string $filter
 * @return array
 */
function changeHtmlSpecialChars($arr,$filter='htmlspecialchars'){
    if(!function_exists($filter))
        return $arr;
    $temp=array();
    foreach($arr as $key => $value){
        if(is_array($value))
            $temp[$key]=changeHtmlSpecialChars($value,$filter);
        else
            $temp[$key]=$filter($value);
    }
    return $temp;
}
/**
 * 由上传原文件名字推算出对应缩略图的名字！
 * @param $filename '文件名'
 * @param $prefix '前缀'
 * @return string '真实地址'
 */
function get_thumb_file($filename,$prefix='m_'){
    return dirname($filename).'/'.$prefix.basename($filename);
}

/**
 * 返回符合规定范围的字符，不够填充，多余取部分
 * @param $str
 * @param int $min
 * @param int $max
 * @param fill string
 * @return string
 */
function pillStr($str,$min=0,$max=20,$fill='*'){
    $len=mb_strlen($str,'utf-8');
    //填充名称不够的部分，截取多出的部分
    if($len>$max){
        return mb_substr($str,0,C('PLAN_MAX_NAME'),'utf-8');
    }elseif($len<$min) {
        $t=C('PLAN_MIN_NAME')-$len;
        for($i=0;$i<$t;$i++)
            $str .= $fill;
    }
    return $str;
}

/**
 * 通过判断字符串长度截取一个字符串，如果字符串过长，则用$fill_len个$fill替换后$fill_len个字符，并返回！
 * @param $str
 * @param int $max
 * @param int $fill_len
 * @param string $fill
 * @return string
 */
function submore($str,$max=8,$fill_len=3,$fill='.'){
    //计算占位 一个汉字两个长度，一个英语一个长度， strlen=3汉字+1英语 mb_strlen=1汉字+1英语 ，相加除2，即为2汉字+1英语，即为占位数
    $len=(mb_strlen($str,'utf-8')+strlen($str))/2;
    $max*=2;//这是占位数，1个汉字，算两个占位，所以允许的占位数*2
    //p($len.':'.$max);
    if($len<$max||$len<$fill_len)
        return $str;
    $str=mb_substr($str,0,mb_strlen($str,'utf-8')-$fill_len,'utf-8');
    for($i=0;$i<$fill_len;$i++){
        $str .=$fill;
    }
    return $str;
}
function Directory( $dir,$num=0644){

    return  is_dir ( $dir ) or Directory(dirname( $dir )) and  mkdir ( $dir , $num);

}

/**
 * 获取n天零点时间戳
 * @param $day
 * @return false|int
 */
function get_time($day){
    //return strtotime(date('Y-m-d',time()+80400*$day));
    return strtotime(date('Y-m-d',time()))+80400*$day;//两种写法可能会不一样，第一种可能会出错，神奇。
}

/**
 * 展示时间，支持格式自定义，在$max天之类的将会转换形态.
 * @param $time
 * @param string $format
 * @param int $max
 * @return false|string
 */
function show_time($time,$format='Y-m-d H:i:s',$max=6){
    $distance=time()-$time;

    //分类返回时间
    if($distance>86400*$max)
        return date($format,$time);

    //获取天数
    $day=round($distance/86400,0);
    $str = $day>2?$day.'天前 ':$day>1?'前天 ':$day>0?'昨天 ':'今天 ';//感觉这个?表达式乱糟糟的。。
    return $str . date('H:i',$time);
}

/**
 * 计算获得的经验！
 * @param $day
 * @return int
 */
function checkoutExp($day){
    $exp=5;
    //最多叠加5次！
    for($i=1;$i<=5&&$i<$day;$i++){
        $exp+=5;
    }
    return $exp;
}

/**
 * 屏蔽部分关键字，比如邮箱信息不能完全提供出来！
 */
function hiddenKey($str,$start=3,$length=5,$replace='*'){
    $result='';
    $len=mb_strlen($str,'utf-8');
    if($len<$start)
        $result=mb_substr($str,0,$len-3).$replace.$replace.$replace;
    else{
        $result=mb_substr($str,0,$start,'utf-8');
        for($i=0;$i<$length;$i++)
            $result.=$replace;
        $result.=mb_substr($str,$start+$length,null,'utf-8');
    }
    return $result;
}

/**
 * 获取用户的扩展信息！
 * @param $uid
 * @return mixed
 */
function get_user_info($uid){
    $data=M('user_config')->where("uid='%d'",$uid)->find();
    return $data;
}

/**
 * 返回$uid对应的用户的未完成任务的邮件content，用于邮件发送。
 * @param $arr          #mail对象传递过来的内容信息数组。
 * @return mixed|null
 */
function get_uncomplete_plan($arr){
    $uid = mb_split('\+',$arr['for']);//mb_split()里边的第一个参数是正则表达式。
    $uid = intval($uid[1]);
    $plan=M('plan_clone')
        ->table(C('DB_PREFIX').'plan_clone AS pc')
        ->join(C('DB_PREFIX').'plan AS p ON p.id=pc.pid')
        ->where("pc.uid='%d' AND pc.complete_time IS null",$uid)//未完成。
        ->field('p.name,pc.id')
        ->select();
    $plans='';
    $db=M('mission_complete');
    $user_config=M('user_config')->find($uid);

    //筛选今日未完成的。
    $map=array(
        'mc.time'   =>  array('between',get_time(0).','.time()),
        'mc.uid'    =>  $uid
    );
    foreach($plan as $val){
        $map['mc.pcid']=$val['id'];
        $sum=$db
            ->table(C('DB_PREFIX').'mission_complete AS mc')
            ->where($map)
            ->join(C('DB_PREFIX').'mission AS m ON m.id=mc.mid')
            ->sum('m.time');
        //如果今天的学习
        if($sum>$user_config['stu_time'])
            continue;
        $plans.='《'.$val['name'].'》、';
    }
    if(empty($plans))
        return null;
    $content=file_get_contents(APP_PATH.'data/mail/info.html');
    $content=str_replace('{__INFO__}','您还有计划<span style="color:#90ed7d">'.$plans.'</span>今日未完成，不要忘记了哦！',$content);
    //p($content);
    return $content;
}

/**
 * //用户信息缓冲，减少数据库读取量
 */
function users_cache($uid){
    global $users_cache;
    if(empty($users_cache))
        $users_cache=array();
    $uid = intval($uid);
    if(!isset($users_cache[$uid])) {
        $tmp = M('user')->field('id,username')->find($uid);//这里的字段用上就加。
        if(empty($tmp))
            $tmp = array('username'=>'无','id'=>0);
        $users_cache[$uid] = $tmp;
    }
    return $users_cache[$uid];
}

/**
 * 清理对应的缓存，支持删除整个文件夹。
 * @param $filename
 * @param bool $isDir
 * @param string $ext
 * @return bool
 */
function clear_cache($filename,$isDir=false,$ext='.html'){
    $path = C('HTML_PATH').'/'.$filename.$ext;
    $dir = dirname($path);
    $path = $dir.'/'.basename($path);           //这里这样写是为了防止写入../之类的地址。
    if(0 !== strpos($path,dirname(C('HTML_PATH').'/xxx.xx')))
        return false;
    //p($path);
    //p($dir);
    if(is_file($path)){
        unlink($path);
        return true;
    }
    if($isDir && is_dir($dir)){//只有在指定删除dir并且确切是dir的时候执行删除。
        //p($dir);
        return rmDirFile($dir);
    }
    return false;
}
function isEmptyAction($module,$action) {
    $className  =   $module.'Action';
    $class      =   new $className;
    return !method_exists($class,$action);
}

/**
 * 使用TP的解析静态文件的方式解析文件
 * @param array $default
 * @return bool|string
 */
function get_cache_file_name($default=array()){
    //合并参数数组
    $default = array_merge(array('group'=>GROUP_NAME,'module'=>MODULE_NAME,'action'=>ACTION_NAME),$default);
    // 分析当前的静态规则
    $htmls = C('HTML_CACHE_RULES'); // 读取静态规则
    if(!empty($htmls)) {
        $htmls = array_change_key_case($htmls);
        // 静态规则文件定义格式 actionName=>array('静态规则','缓存时间','附加规则')
        // 'read'=>array('{id},{name}',60,'md5') 必须保证静态规则的唯一性 和 可判断性
        // 检测静态规则
        $moduleName = strtolower($default['module']);
        $actionName = strtolower($default['action']);
        if (isset($htmls[$moduleName . ':' . $actionName])) {
            $html = $htmls[$moduleName . ':' . $actionName];   // 某个模块的操作的静态规则
        } elseif (isset($htmls[$moduleName . ':'])) {// 某个模块的静态规则
            $html = $htmls[$moduleName . ':'];
        } elseif (isset($htmls[$actionName])) {
            $html = $htmls[$actionName]; // 所有操作的静态规则
        } elseif (isset($htmls['*'])) {
            $html = $htmls['*']; // 全局静态规则
        } elseif (isset($htmls['empty:index']) && !class_exists(MODULE_NAME . 'Action')) {
            $html = $htmls['empty:index']; // 空模块静态规则
        } elseif (isset($htmls[$moduleName . ':_empty']) && isEmptyAction(MODULE_NAME, ACTION_NAME)) {
            $html = $htmls[$moduleName . ':_empty']; // 空操作静态规则
        }
        if (!empty($html)) {
            // 解读静态规则
            $rule = $html[0];
            // 以$_开头的系统变量
            $rule = preg_replace('/{\$(_\w+)\.(\w+)\|(\w+)}/e', "\\3(\$\\1['\\2'])", $rule);
            $rule = preg_replace('/{\$(_\w+)\.(\w+)}/e', "\$\\1['\\2']", $rule);
            // {ID|FUN} GET变量的简写
            $rule = preg_replace('/{(\w+)\|(\w+)}/e', "\\2(\$_GET['\\1'])", $rule);
            $rule = preg_replace('/{(\w+)}/e', "\$_GET['\\1']", $rule);
            // 特殊系统变量
            $rule = str_ireplace(
                array('{:app}', '{:module}', '{:action}', '{:group}'),
                array(APP_NAME, MODULE_NAME, ACTION_NAME, defined('GROUP_NAME') ? GROUP_NAME : ''),
                $rule);
            // {|FUN} 单独使用函数
            $rule = preg_replace('/{|(\w+)}/e', "\\1()", $rule);
            if (!empty($html[2])) $rule = $html[2]($rule); // 应用附加函数

            // 当前缓存文件
            return HTML_PATH . $rule . C('HTML_FILE_SUFFIX');
        }
    }
    return false;
}

/**
 * 递归删除文件夹中文件和文件夹
 * @param $path
 * @param bool $rmDir
 * @return bool
 */
function rmDirFile($path,$rmDir=true){
    if(!is_dir($path))
        return false;
    $handle = opendir($path);
    if(!$handle)
        return false;
    while(false !== ($item = readdir($handle))) {
        if ($item != '.' && $item != '..') {
            $real_item = $path . '/' . $item;
            is_dir($real_item) ? rmDirFile($real_item) : unlink($real_item);
        }
    }
    if($rmDir)
        return rmdir($path);
    return true;
}
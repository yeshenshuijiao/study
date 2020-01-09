<?php
/*
 放入网站根目录
 */

$appId = '62177';  //  （请勿修改）
$appKey = '5db38c26c79351b792d88753bd467d36';//（请勿修改）

//====无需修改下面程序====//

$host = "https://cms.tkjidi.com/";

// 异常字符去除
//http://cmst.tkjd.com/index.php/favicon.ico?r=er
//http://cmst.tkjd.com/favicon.ico?r=er
//http://cmst.tkjd.com/favicon.ico
$requestUrl     = @$_SERVER["REQUEST_URI"];
$documentUrl    = @$_SERVER['PHP_SELF'];

if (preg_match('/index.php\/([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\?r=/', $_SERVER['REQUEST_URI'], $matches)) {
    $requestUrl     = str_replace('/'.$matches[1].'.'.$matches[2], '', $_SERVER['REQUEST_URI']);
    $documentUrl    = str_replace('/'.$matches[1].'.'.$matches[2], '', $_SERVER['PHP_SELF']);
    header('Location: '.$requestUrl);
} elseif (preg_match('/\/([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\?r=/', $_SERVER['REQUEST_URI'], $matches) OR preg_match('/\/([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)/', $_SERVER['REQUEST_URI'], $matches)) {
    if ($matches[1]!='index' && $matches[2]!='php') {
        $requestUrl     = str_replace('/'.$matches[1].'.'.$matches[2], '', $_SERVER['REQUEST_URI']);
        $documentUrl    = str_replace('/'.$matches[1].'.'.$matches[2], '', $_SERVER['PHP_SELF']);
        header('Location: '.$requestUrl);
    }
}
$requestMethod = strtoupper(@$_SERVER["REQUEST_METHOD"]);  //get

$cache = new Cacher();

$cache->delFile();

if (isset($_REQUEST['clean'])) {
    $cache->clean();
    echo '已清除缓存';
    exit;
}
$key = md5($requestUrl . Cacher::isMobile() . Cacher::isIPad() . Cacher::isIPhone() . Cacher::isMicroMessenger());
if ($requestMethod == 'GET') {
    $cacheData = $cache->Get($key);
    if ($cacheData !== false) {
        echo $cacheData;
        exit;
    }
	
}
$httpHelper = new Httper($appId, $appKey, $documentUrl);
$html = $httpHelper->getHtml($host, $requestUrl, $requestMethod == 'POST' ? @$_POST : array(), $requestMethod);

if ($requestMethod == 'GET' && !empty($html)) {
    $cache->Set($key, $html, 60);
}

echo $html;

class Httper
{
    protected $appId;
    protected $key;
    protected $documentUrl;

    public function __construct($appId, $key, $documentUrl)
    {
        $this->appId = $appId;
        $this->key = $key;
        $this->documentUrl = $documentUrl;
    }


    /**
     * @param $url
     * @param $requestUrl
     * @param array $param
     * @param string $method
     * @param bool $isAjax
     * @param string $cookie
     * @param string $refer
     * @param null $userAgent
     * @return string
     */
    public function getHtml($url, $requestUrl, $param = array(), $method = 'GET', $isAjax = null, $cookie = NULL, $refer = null, $userAgent = null)
    {
	   $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 1);
        empty($refer) && $refer = @$_SERVER['HTTP_REFERER'];
        $ua = $userAgent;
        empty($ua) && $ua = @$_SERVER['HTTP_USER_AGENT'];
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $header = array(
            'APPID: ' . $this->appId,
            'APPKEY: ' . $this->key,
            'CMS-HOST: ' . @$_SERVER["HTTP_HOST"],
            'DOCUMENT-URL: ' . $this->documentUrl,
            'REQUEST-URL: ' . $requestUrl,
			'PROTOCOL: ' . $protocol,
        );
		
        $_isAjax = false;
        if ($isAjax) {
            $_isAjax = true;
        }
        if (!$_isAjax && $isAjax === null) {
            $_isAjax = $this->getIsAjaxRequest();
        }
        if ($_isAjax) {
            $header[] = 'X_REQUESTED_WITH: XMLHttpRequest';
        }
		
        $clientIp = $this->get_real_ip();
		
        if (!empty($clientIp)) {
            $header[] = 'CLIENT-IP: ' . $clientIp;
            $header[] = 'X-FORWARDED-FOR: ' . $clientIp;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if (empty($cookie)) {
            $cookie = $_COOKIE;
        }
        if (is_array($cookie)) {
            $str = '';
            foreach ($cookie as $k => $v) {
                $str .= $k . '=' . $v . '; ';
            }
            $cookie = $str;
        }
        if (!empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
		
		
		$houzhuiarr=array();
		$houzhui="";
		if(strpos($requestUrl,'.php')!==false)
		{
			$houzhuiarr=explode(".php",$requestUrl);
			$houzhui=$houzhuiarr[count($houzhuiarr)-1];
			//echo $houzhui.'111';
		}
		else
		{
			if(strpos($requestUrl,'?')===false)
			{
				$houzhui="";
			}
			else
			{
				$houzhuiarr=explode("?",$requestUrl);
				$houzhui="?".$houzhuiarr[count($houzhuiarr)-1];
			}
		}
		
		
        if (strtolower($method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if ($param) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
            }
           
            curl_setopt($ch, CURLOPT_URL, $url.$houzhui);
        } else {
			
            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
            if ($param) {
				/*echo "fsfs";
				exit;*/
                $urlInfo = parse_url($url);
                $q = array();
                if (isset($urlInfo['query']) && !empty($urlInfo['query'])) {
                    parse_str($urlInfo['query'], $q);
                }
                $q = array_merge($q, $param);
                $cUrl = sprintf('%s://%s%s%s%s',
                    $urlInfo['scheme'],
                    $urlInfo['host'],
                    isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '',
                    isset($urlInfo['path']) ? $urlInfo['path'] : '',
                    count($q) ? '?' . http_build_query($q) : '');
                curl_setopt($ch, CURLOPT_URL, $cUrl);
            } else {
				
                curl_setopt($ch, CURLOPT_URL, $url.$houzhui);
            }
        }

        $r = curl_exec($ch);
        
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = mb_substr($r, 0, $headerSize);
        $r = mb_substr($r, $headerSize);
        curl_close($ch);
        unset($ch);
        $headers = explode("\r\n", $header);
        foreach ($headers as $h) {
            $h = trim($h);
            if (empty($h) || preg_match('/^(HTTP|Connection|EagleId|Server|X\-Powered\-By|Date|Transfer\-Encoding|Content)/i', $h)) {
                continue;
            }
            header($h);
        }
        return $r;
    }

    function get_real_ip()
    {
        if (@$_SERVER["HTTP_X_FORWARDED_FOR"]) {
            $ip = @$_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (@$_SERVER["HTTP_CLIENT_IP"]) {
            $ip = @$_SERVER["HTTP_CLIENT_IP"];
        } elseif (@$_SERVER["REMOTE_ADDR"]) {
            $ip = @$_SERVER["REMOTE_ADDR"];
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = "";
        }
        return $ip;
    }

    public function getIsAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

class Cacher
{
    protected $dir = '';

    public function __construct()
    {
        
		$this->dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache';
        if (is_dir($this->dir)) {
            return;
        }
        @mkdir($this->dir);
    }

    public function Set($key, $value, $expire = 360)
    {
        $data = array(
            'time' => time(),
            'expire' => $expire,
            'value' => $value
        );
        @file_put_contents($this->dir . DIRECTORY_SEPARATOR . md5($key) . 'cache', serialize($data));
		
    }

    public function Get($key)
    {

        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key) . 'cache';
        if (!file_exists($file)) {
            return false;
        }
        $str = @file_get_contents($file);
        if (empty($str)) {
            return false;
        }
        $data = @unserialize($str);
        if (!isset($data['time']) || !isset($data['expire']) || !isset($data['value'])) {
            return false;
        }
        if ($data['time'] + $data['expire'] < time()) {
            return false;
        }
        return $data['value'];
    }

    static function isMobile()
    {
        $ua = @$_SERVER['HTTP_USER_AGENT'];
        return preg_match('/(iphone|android|Windows\sPhone)/i', $ua);
    }

    public function clean()
    {
        if (!empty($this->dir) && is_dir($this->dir)) {
            @rmdir($this->dir);
        }
        $files = scandir($this->dir);
        foreach ($files as $file) {
            @unlink($this->dir . DIRECTORY_SEPARATOR . $file);
        }
    }

    public function delFile()
    {
        $dir=$this->dir."/";
        $sec=60*10;
        if(is_dir($dir)){
            $files = scandir($dir);
            foreach($files as $filename){
                if($filename=='.'||$filename=='..')
            {
                
                continue;
            }
                $worn_time=filectime($dir.$filename);
                $new_time=time();
                $time=$new_time-$worn_time;
                if($time>$sec){
                    if(!is_dir($dir.''.$filename)){
                        unlink($dir.''.$filename);
                    }
                }
            }
        }
    }
    
    static function isMicroMessenger()
    {
        $ua = @$_SERVER['HTTP_USER_AGENT'];
        return preg_match('/MicroMessenger/i', $ua);
    }

    static function isIPhone()
    {
        $ua = @$_SERVER['HTTP_USER_AGENT'];
        return preg_match('/iPhone/i', $ua);
    }

    static function isIPad()
    {
        $ua = @$_SERVER['HTTP_USER_AGENT'];
        return preg_match('/(iPad|)/i', $ua);
    }
}

<?php
namespace Orq\Wxpn;

class Weixin
{
    var $appid = "";
    var $appsecret = "";
    var $access_token; // 非auth的access_token
    var $expires_time; // 非auth的access_token的过期时间
 
    //构造函数，获取Access Token
    public function __construct($appid = NULL, $appsecret = NULL)
    {
        if($appid && $appsecret){
            $this->appid = $appid;
            $this->appsecret = $appsecret;
        }

        $file = __DIR__ . '/access_token.json';
        $res = file_get_contents($file);
        $result = json_decode($res, true);
        $this->expires_time = $result["expires_time"];
        $this->access_token = $result["access_token"];
      
        if (time() > ($this->expires_time + 3600)){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
            $res = $this->request($url);
            $result = json_decode($res, true);
            $this->access_token = $result["access_token"];
            $this->expires_time = time();
            file_put_contents($file, '{"access_token": "'.$this->access_token.'", "expires_time": '.$this->expires_time.'}');
        }
    }

    /**
     * 验证url
     * @param string $token 用于验证url的token
     * @return string|void 当验证通过时返回微信发送的`echostr`
     */
    public static function validateUrl($token)
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            return $echoStr;
        }
    }

    /**
     * 记录日志，可用于调试
     * @param string $log_content 记录的内容
     * @return void
     */
    private function log($log_content)
    {
        $max_size = 500000;
        $log_filename = "wxpn_log.xml";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('Y-m-d H:i:s').$log_content."\r\n", FILE_APPEND);
    }

    /**
     * 发送HTTP请求，如果data为null会使用post方法
     */
    protected function request($url, $data = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
<?php
namespace Orq\Wxpn;

/**
 * 消息相关
 * 微信开发文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140453
 */
class Message extends Weixin
{
    protected $welcomeMsg = '欢迎关注';
    protected $qrcodeJoinSubscribers = [];

    /**
     * @param string $msg
     * @return void
     */
    public function setWelcomeMsg($msg)
    {
        $this->welcomeMsg = $msg;
    }

    /**
     * 响应用户发送的消息
     */
    public function responseMsg()
    {
        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $type = trim($postObj->MsgType);
                
            //消息类型分离
            switch ($type)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
            }
            $this- log("T \r\n".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }
     
    //接收事件消息
    private function receiveEvent($object)
    {
        $content = "";
        switch (strtolower($object->Event))
        {
            case "subscribe":
                $content = $this->welcomeMsg;
                if (isset($object->EventKey)){
                    $sceneid = str_replace("qrscene_","",$object->EventKey);
                    // 获取用户信息并register用户
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$this->access_token&openid=$object->FromUserName&lang=zh_CN";
                    $info = $this->request($url);
                    foreach ($this->qrcodeJoinSubscribers as $subscriber) {
                        $subscriber->notify($info, $sceneid);
                    }
                }
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "click":
                $content = $object->EventKey;
                break;
            default:
                $content = '';
                break;
        }
    
        if(is_array($content)){
            $result = $this->transmitNews($object, $content);
        }else{
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }
    
    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
    
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
    
        return $result;
    }
    
    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>%s</ArticleCount>
    <Articles>
$item_str    </Articles>
</xml>";
    
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    public function subscribeQrcodeJoin(QrcodeJoinSubstriberInterface $subscriber)
    {
        if (!in_array($subscriber, $this->qrcodeJoinSubscribers)) {
            array_push($this->qrcodeJoinSubscribers, $subscriber);
        }
    }

    public function unsubscribeQrcodeJoin(QrcodeJoinSubstriberInterface $subscriber)
    {
        $k = array_search($subscriber,$this->qrcodeJoinSubscribers);
        if ($k !== FALSE) unset($this->qrcodeJoinSubscribers[$k]);
    }
}
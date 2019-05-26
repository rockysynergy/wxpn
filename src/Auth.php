<?php
namespace Orq\Wxpn;

/**
 * 处理微信授权登录
 * 微信接口文档详情：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
 */
class Auth extends WeiXin
{
    /**
     * 第一步：生成进行oauth2授权登录的url
     * @param string $redirect_url 用户同意授权后跳转到此url，code会以url参数传递到此url
     * @param string $scope snsapi_base|snsapi_userinfo
     * @param string $state 用户自定义的字符串，会和code一起返回
     * @return string
     */
    public function oauth2AuthorizeUrl($redirect_url, $scope, $state = NULL)
    {
        if (!in_array($scope, ['snsapi_base', 'snsapi_userinfo'])) throw new Exception\IllegalArgumentException('scope参数只能是snsapi_base或snsapi_userinfo。实际收到的是：'.$scope);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_url."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        return $url;
    }
    
    /**
     * 第二步：使用第一步得到的code获取access_token（oauth2专用 )。
     * 这一步获取了access_token的同时也获取了openid。如果第一步的scope为snsapi_base，那么授权操作流程到此结束
     * 
     * @param string $code 第一步获取的code
     * @return array 
     */
    public function oauth2AccessToken($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->request($url);
        return json_decode($res, true);
    }

    /**
     * 第三步：使用前一步收到的openid和access_token获取用户基本信息
     * @param string $access_token
     * @param string $openid
     * @return string
     */
    public function oauth2GetUserInfo($access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&ng=zh_CN";
        $res = $this->request($url);
        return $res;
    }

    /**
     * 知道用户openid的情况下获取用户基本信息（在网页调用无效)
     * @param string $openid
     */
    public function getUserInfo($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->request($url);
        return json_decode($res, true);
    }
}
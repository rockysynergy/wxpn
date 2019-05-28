<?php
namespace Orq\Wxpn;

/**
 * 创建自定义菜单
 * 微信开发文档地址：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1455782296
 */
class Menu extends WeiXin
{
    /**
     * 创建菜单
     * @param array $button 菜单定义
     * @param array $matchrule 菜单匹配规则
     * @return array
     */
    public function create($button, $matchrule = null)
    {
        foreach ($button as &$item) {
            foreach ($item as $k => $v) {
                if (is_array($v)){
                    foreach ($item[$k] as &$subitem) {
                        foreach ($subitem as $k2 => $v2) {
                            $subitem[$k2] = urlencode($v2);
                        }
                    }
                }else{
                    $item[$k] = urlencode($v);
                }
            }
        }
 
        if (isset($matchrule) && !is_null($matchrule)){
            foreach ($matchrule as $k => $v) {
                $matchrule[$k] = urlencode($v);
            }
            $data = urldecode(json_encode(array('button' => $button, 'matchrule' => $matchrule)));
            $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=".$this->access_token;
        }else{
            $data = urldecode(json_encode(array('button' => $button)));
            $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->access_token;
        }
        $res = $this->request($url, $data);
        return json_decode($res, true);
    }
}
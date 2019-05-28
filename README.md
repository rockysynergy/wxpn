# wxpn
微信公众号开发PHP库

# 安装

# 手册

## Oauth2 （网页授权）

1. 根据[文档](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842)完成配置
2. 代码调用示例：

```PHP
// 第一步
$wAuth = new Orq\Wxpn\Auth($appid, $secret);
$redirect_url = 'http://my.com/path_for_wxlogin';
$jumpurl = $wAuth->oauth2AuthorizeUrl($redirect_url, "snsapi_userinfo", "123");
header("Location: $jumpurl");

// 第二步 (http://my.com/path_for_wxlogin所在的页面)
$code = $_GET['code'];
$access_token_oauth2 = $wAuth->oauth2AccessToken($code); // 如果scope是snsapi_base则流程到此结束

// 第三步
$userinfo = $this->wAuth-> oauth2GetUserInfo($access_token_oauth2['access_token']
```

## 自定义菜单

1. [开发文档](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141013)
2. 代码示例

```PHP
$wMenu = new Orq\Wxpn\Menu($appid, $secret);
$button = [
    [
        'name' => '享睡雅兰',
        'sub_button' => [
            [
                'type' => "view",
                'name' => "关于我们",
                'url'  => "http://www.my_domain.com/about_us"
            ],
            [
                'type' => 'click',
                'name' => '客服电话',
                'key' => '40088223344'
            ]
        ]
    ]
];
$wMenu->create($button);
```

3. 假设上面的代码在https://mydoman.com/create_menu，访问此url

## 生成渠道二维码海报

1. [文档](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1443433542)
2. 示例代码

```PHP
$wQrCode = new Orq\Wxpn\QrCode($appid, $secret);
$sceneId = 33;
$qrPath = './path_to_store/'.$sceneId.'.jpg'
$wQrCode->getQrCode($type, $sceneId, $qrPath);

$wQrCode->makePoster($qrPath, $bgFilePath, $targetPath);
```

3. 海报文件存储在$targetPath
4. 用户扫描海报时会发送`订阅`事件，[文档](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140454)
5. 代码调用示例

```PHP
// 首先需要实现接口
class RegisterService implements Orq\Wxpn\QrcodeJoinSubstriberInterface
{
    /**
     * $userInfo 为新用户的信息
     * $sceneId 为场景（渠道）值
     */
    public function notify(array $userInfo, string $sceneId):void
    {
    }
}

$registerService = new RegisterService();
$wMessage = new Orq\Wxpn\Message($appid, $secret);
$wMessage->subscribeQrcodeJoin（$regiseterService);
```
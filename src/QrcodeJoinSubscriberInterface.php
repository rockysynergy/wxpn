<?php
namespace Orq\Wxpn;

interface QrcodeJoinSubstriberInterface
{
    public function notify(array $userInfo, string $sceneId):void;
}
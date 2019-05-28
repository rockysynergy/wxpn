<?php
namespace Orq\Wxpn;

/**
 * 微信公众号带参数的二维码（渠道二维码）相关的代码
 * 微信文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1443433542
 */
class QrCode extends Weixin
{
    /**
     * 生成渠道二维码
     * @param string $type QR_SCENE|QR_STR_SCENE|QR_LIMIT_SCENE|QR_LIMIT_STR_SCENE
     * @param int|string $scene_id 场景值
     * @param string $qrcode_path 保存二维码的路径
     * @return void
     */
    public function getQrcode($type, $scene_id, $qrcode_path)
    {
        if (!in_array($type, ['QR_SCENE', 'QR_STR_SCENE', 'QR_LIMIT_SCENE', 'QR_LIMIT_STR_SCENE'])) {
            throw new Exceptions\IllegalArgumentException('二维码类型错误，正确值为 ` QR_SCENE|QR_STR_SCENE|QR_LIMIT_SCENE|QR_LIMIT_STR_SCENE` .实际是：'.$type);
        }
        if ($type == 'QR_SCENE' || $type == 'QR_LIMIT_SCENE') $scene_id = (int) $scene_id;
        if ($type == 'QR_STR_SCENE' || $type == 'QR_LIMIT_STR_SCENE') $scene_id = (string) $scene_id;

        $qrcode = json_encode(["action_name"=>$type, "action_info"=>["scene"=> ["scene_id"=>$scene_id]]]);
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$this->access_token";
        $result = $this->request($url, $qrcode);
        $jsoninfo = json_decode($result, true);
        $ticket = $jsoninfo["ticket"];
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        $imageInfo = $this->downloadWeixinFile($url);

        $local_file = fopen($qrcode_path, 'w');
        fwrite($local_file, $imageInfo["body"]);
        fclose($local_file);
    }

    /**
     * 下载二维码图片
     */
    protected function downloadWeixinFile($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);    
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('body' =>$package), array('header' =>$httpinfo)); 
        return $imageAll;
    }

    /**
     * 合成二维码图片海报
     * @param string $qrcodeFullPath 二维码图片的文件地址
     * @param string $bgPath 海报背景图片地址
     * @param string $targetPath 目标地址
     * @return void
     */
    public function makePoster($qrcodeFullPath, $bgPath, $targetPath) {
        $config = array(
            'image'=>array(
                array(
                    'url'=>$qrcodeFullPath,     //二维码资源
                    'stream'=>0,
                    'left'=>440,
                    'top'=>935,
                    'right'=>0,
                    'bottom'=>0,
                    'width'=>250,
                    'height'=>250,
                    'opacity'=>100
                )
            ),
            'background'=>$bgPath //背景图
        );
        //echo createPoster($config,$targetPath);
        $this->createPoster($config, $targetPath);
    }
    
    /**
     * 生成宣传海报
     * @param array  参数,包括图片和文字
     * @param string  $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
     * @return mixed 
     */
    public function createPoster($config=array(),$filename=""){
        //如果要看报什么错，可以先注释调这个header
        if(empty($filename)) header("content-type: image/png");
        $imageDefault = array(
            'left'=>0,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>100,
            'height'=>100,
            'opacity'=>100
        );
        $textDefault = array(
            'text'=>'',
            'left'=>0,
            'top'=>0,
            'fontSize'=>32,       //字号
            'fontColor'=>'255,255,255', //字体颜色
            'angle'=>0,
        );
        $background = $config['background'];//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));
        //处理了图片
        if(!empty($config['image'])){
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault,$val);
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom'.image_type_to_extension($info[2], false);
                if($val['stream']){   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = $function($val['url']);
                $resWidth = $info[0];
                $resHeight = $info[1];
                //建立画板 ，缩放图片至指定尺寸
                $canvas=imagecreatetruecolor($val['width'], $val['height']);
                imagefill($canvas, 0, 0, $color);
                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
                //放置图像
                imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度
            }
        }
        //处理文字
        if(!empty($config['text'])){
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault,$val);
                list($R,$G,$B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];
                imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);
            }
        }
        //生成图片
        if(!empty($filename)){
            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
            imagedestroy($imageRes);
            if(!$res) return false;
            return $filename;
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }
    }
}
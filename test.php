<?php
/**
 * Created by PhpStorm.
 * User: yuqing
 * Date: 2018-12-22
 * Time: 16:27
 *
 */
header("Content-type: text/html; charset=utf-8");
require('config.php');
require('common.php') ;
$access_token = access_token($config['app_id'],$config['app_secret']);
//var_dump($access_token);
//创建菜单
function add_menu($access_token){
    $url ='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
    $jsonmenu = '{
    "button": [
        {
            "name": "扫码", 
            "sub_button": [
                {
                    "type": "scancode_waitmsg", 
                    "name": "扫码带提示", 
                    "key": "rselfmenu_0_0"
                }, 
                {
                    "type": "scancode_push", 
                    "name": "扫码推事件", 
                    "key": "rselfmenu_0_1"
                }
            ]
        }, 
        {
            "name": "发图", 
            "sub_button": [
                {
                    "type": "pic_sysphoto", 
                    "name": "系统拍照发图", 
                    "key": "rselfmenu_1_0"
                }, 
                {
                    "type": "pic_photo_or_album", 
                    "name": "拍照或者相册发图", 
                    "key": "rselfmenu_1_1"
                }, 
                {
                    "type": "pic_weixin", 
                    "name": "微信相册发图", 
                    "key": "rselfmenu_1_2"
                }
            ]
        }, 
        {
            "name": "其他", 
            "sub_button": [
                {
                    "name": "发送位置", 
                    "type": "location_select", 
                    "key": "rselfmenu_2_0"
                }, 
                {
                    "type": "click", 
                    "name": "今日歌曲", 
                    "key": "V1001_TODAY_MUSIC"
                }, 
                {
                    "type": "view", 
                    "name": "搜索", 
                    "url": "http://www.soso.com/"
                }
            ]
        }
    ]
}
';
        $res = curl($url,$jsonmenu);
        return $res;
}
//$r = add_menu($access_token);

//获取用户列表
function user($access_token){
    $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}";
    $res = curl($url);
    return json_decode($res);
}
//$r = user($access_token);

//获取用户信息
function user_info($access_token,$app_id){
    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$app_id}&lang=zh_CN";
    $res = curl($url);
    return json_decode($res);
}
$r = user_info($access_token,'oigOC563JTjYxNRK6nr_F5Nrgamk');
dump($r);
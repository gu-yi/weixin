<?php
//require 'config.php';
//获取access_token
function access_token($app_id,$app_secret){
    if(!$app_id || !$app_secret){
      return false;
    }
    $rs = file_get_contents('access_token.json');
    $token = json_decode($rs,true);
    if(time()>($token['expires_time']+6000)){//文件缓存
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$app_id}&secret={$app_secret}";
        $rs = curl($url);
        $token = json_decode($rs,true);
        file_put_contents('access_token.json', '{"access_token": "'.$token['access_token'].'", "expires_time": '.time().'}');
    }
    return $token['access_token'];
}
//curl请求
function curl($url, $data = null)
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
//打印输出
function dump($arr){
   echo '<pre>';
   print_r($arr) ;
}
//生成OAuth2的URL
 function oauth2_url($appid,$redirect_url, $scope, $state = NULL)
{
    $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_url."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
    return $url;
}

//生成OAuth2的Access Token
 function oauth2_access_token($appid,$appsecret,$code)
{
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
    $res = $this->http_request($url);
    return json_decode($res, true);
}
//获取用户基本信息（OAuth2 授权的 Access Token 获取 未关注用户，Access Token为临时获取）
function oauth2_get_user_info($access_token, $openid)
{
    $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
    $res = $this->http_request($url);
    return json_decode($res, true);
}



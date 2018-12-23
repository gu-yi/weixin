<?php
/**
 * Created by PhpStorm.
 * User: yuqing
 * Date: 2018-12-19
 * Time: 11:52
 */
header('Content-type:text');
define("TOKEN", "weixin");

$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
    /*$content ='pp';
    $content = $content.'|'.date('Y-m-d H:i:s')."\nREMOTE_ADDR:".$_SERVER["REMOTE_ADDR"]."\nQUERY_STRING:".$_SERVER["QUERY_STRING"]."\n\n";

    if (isset($_SERVER['HTTP_APPNAME'])){   //SAE
        sae_set_display_errors(false);
        sae_debug(trim($content));
        sae_set_display_errors(true);
    }else {
        $max_size = 100000;
        $log_filename = "log.xml";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, $content, FILE_APPEND);
    }*/
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}

class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }
    //签名验证
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    //消息发送
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        if (!empty($postStr)){
            $this->log('R'.$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $RX_TYPE = $postObj->MsgType;
            switch ($RX_TYPE){
                case 'text':
                    $result = $this->text($postObj);
                    break;
                case 'image':
                    $result = $this->image($postObj);
                    break;
                case 'voice':
                    $result = $this->voice($postObj);
                    break;
                case 'event':
                    $result = $this->event($postObj);
                    break;
                default:
                    $result = '未知类型:'.$RX_TYPE;
                    break;
            }
            $this->log('T'.$result);
            echo $result;

        }else{
            echo "";
            exit;
        }
    }
    //关注取消
    private function event($postObj){
        $content = '';
        $event = $postObj->Event;
        if($event == 'subscribe'){//关注
            $content = '感谢您的关注!';
        }elseif ($event=='unsubscribe'){
            $content = '取消关注';
        }elseif ($event=='LOCATION'){//用户位置信息
            //坐标转化地址
            $url = "http://api.map.baidu.com/geocoder/v2/?ak=B944e1fce373e33ea4627f95f54f2ef9&location=$postObj->Latitude,$postObj->Longitude&output=json&coordtype=gcj02ll";
            $output = file_get_contents($url);
            $address = json_decode($output, true);
            //$content = "位置 ".$address["result"]["addressComponent"]["province"]." ".$address["result"]["addressComponent"]["city"]." ".$address["result"]["addressComponent"]["district"]." ".$address["result"]["addressComponent"]["street"];
            $content = "位置 ".$address["result"]["formatted_address"]." 附近 ".$address["result"]["business"];
        }elseif ($event=='CLICK'){
            if($postObj->EventKey == 'V1001_TODAY_MUSIC'){//获取音乐
                $content = array();
                $content[] = array("Title"=>"音乐",  "Description"=>"今日音乐", "PicUrl"=>"https://images.pexels.com/photos/532420/pexels-photo-532420.jpeg?auto=compress&cs=tinysrgb&dpr=1&w=500", "Url" =>"http://music.taihe.com/song/610370676");

            }

        }else{
            $content = '未知类型'.$event;
        }
        if($postObj->EventKey == 'V1001_TODAY_MUSIC') {//获取音乐
            $res = $this ->r_pic($postObj,$content);
        }else{
            $res = $this ->r_text($postObj,$content);
        }

        return $res;

    }
    /**
     * 文本消息
     */
    private function text($postObj){
        $keyword = trim($postObj->Content);
        if (strstr($keyword,'时间')){
            $content = '现在时间:'.date("Y-m-d H:i:s",time());
        }elseif (strstr($keyword,'授权')){
            $content = "授权体验\n<a href='https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx3385e836f6b5aec7&redirect_uri=http://www.doubleone.top/weixin/retrun.php&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect'>点击这里体验</a>";
        }elseif (strstr($keyword,'图片')){
            $content = array('MediaId'=>'qoLsYfF_GEaVrPHJXrsYLvfj-nqS5DRuxoJ4KMFedYb-w--44hkB0Lb9rSVFm7pS');
        }elseif (strstr($keyword,'语音')){
            $content = array('MediaId'=>'wzrAGXNDZsXCHysejzoaX9WBcZ5xNdY9Y4vrWwWyvQYTpAPBaa4R65VekAf6Ocmg');
        }elseif (strstr($keyword,'天气')){
            $city = str_replace('天气','',$keyword);
            $content = $this->get_weather($city);
        }else if (strstr($keyword, "单图文")){
            $content = array();
            $content[] = array("Title"=>"美图",  "Description"=>"美丽的图", "PicUrl"=>"https://images.pexels.com/photos/532420/pexels-photo-532420.jpeg?auto=compress&cs=tinysrgb&dpr=1&w=500", "Url" =>"https://www.pexels.com");
        }else if (strstr($keyword, "图文") || strstr($keyword, "多图文")){
            $content = array();
            $content[] = array("Title"=>"1标题", "Description"=>"一内容", "PicUrl"=>"https://images.pexels.com/photos/37345/underwear-beauty-model-western-model-offered.jpg?auto=compress&cs=tinysrgb&h=650&w=940", "Url" =>"https://www.pexels.com");
            $content[] = array("Title"=>"2标题", "Description"=>"二内容", "PicUrl"=>"https://images.pexels.com/photos/37345/underwear-beauty-model-western-model-offered.jpg?auto=compress&cs=tinysrgb&h=650&w=940", "Url" =>"http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title"=>"3标题", "Description"=>"三内容", "PicUrl"=>"https://images.pexels.com/photos/37345/underwear-beauty-model-western-model-offered.jpg?auto=compress&cs=tinysrgb&h=650&w=940", "Url" =>"http://m.cnblogs.com/?u=txw1958");
        }else{
            $content = '这是一个文本消息';
        }
        //<a href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx9a000b615d89c3f1&redirect_uri=http://mascot.duapp.com/oauth2.php&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect">点击这里体验</a>
        //wzrAGXNDZsXCHysejzoaX9WBcZ5xNdY9Y4vrWwWyvQYTpAPBaa4R65VekAf6Ocmg
        if(is_array($content)){
            if (strstr($keyword,'时间')){
                $res = $this->r_image($postObj,$content);
            }elseif(strstr($keyword,'语音')){
                $res = $this->r_voice($postObj,$content);
            }else if (strstr($keyword, "图文") || strstr($keyword, "天气") ){
                $res = $this->r_pic($postObj,$content);
            }

        }else{
            $res = $this->r_text($postObj,$content);
        }

        return $res;

    }
    //图片信息
    private function image($postObj){
        $content = array("MediaId"=>$postObj->MediaId);
        $res = $this->r_image($postObj,$content);
        return $res;
    }
    //语音信息信息
    private function voice($postObj){
        $content = array("MediaId"=>$postObj->MediaId);
        $res = $this->r_voice($postObj,$content);
        return $res;
    }
    //回复文本信息
    private  function r_text($postObj,$content){
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $time = time();
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
        $result = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $content);
        return $result;
    }
    //回复图文消息
    private function r_pic($postObj,$content)
    {
        if(!is_array($content)){
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
        foreach ($content as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>%s</ArticleCount>
    <Articles>
        $item_str    
    </Articles>
    </xml>";

        $result = sprintf($xmlTpl, $postObj->FromUserName, $postObj->ToUserName, time(), count($content));
        return $result;
    }
    //回复图片信息
    private  function r_image($postObj,$url){
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $time = time();
        $url = $url['MediaId'];
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Image>
                            <MediaId><![CDATA[%s]]></MediaId>
                        </Image>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
        $result = sprintf($textTpl, $fromUsername, $toUsername, $time, 'image', $url);
        return $result;
    }
    //回复语音信息
    private  function r_voice($postObj,$url){
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $time = time();
        $url = $url['MediaId'];
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Voice>
                            <MediaId><![CDATA[%s]]></MediaId>
                        </Voice>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
        $result = sprintf($textTpl, $fromUsername, $toUsername, $time, 'voice', $url);
        return $result;
    }
   //记录日志
   public function log($content){
        $content = $content.'|'.date('Y-m-d H:i:s')."\nREMOTE_ADDR:".$_SERVER["REMOTE_ADDR"]."\nQUERY_STRING:".$_SERVER["QUERY_STRING"]."\n\n";

        if (isset($_SERVER['HTTP_APPNAME'])){   //SAE
            sae_set_display_errors(false);
            sae_debug(trim($content));
            sae_set_display_errors(true);
        }else {
            $max_size = 100000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
            file_put_contents($log_filename, $content, FILE_APPEND);
        }
    }
    //百度天气接口
    function get_weather($cityName)
    {
        $ak = 'WT7idirGGBgA6BNdGM36f3kZ';
        $sk = 'uqBuEvbvnLKC8QbNVB26dQYpMmGcSEHM';
        $url = 'http://api.map.baidu.com/telematics/v3/weather?ak=%s&location=%s&output=%s&sn=%s';
        $uri = '/telematics/v3/weather';
        $location = $cityName;
        $output = 'json';
        $querystring_arrays = array(
            'ak' => $ak,
            'location' => $location,
            'output' => $output
        );
        $querystring = http_build_query($querystring_arrays);
        $sn = md5(urlencode($uri.'?'.$querystring.$sk));
        $targetUrl = sprintf($url, $ak, urlencode($location), $output, $sn);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);
        if ($result["error"] != 0){
            return $result["status"];
        }
        $curHour = (int)date('H',time());
        $weather = $result["results"][0];
        //因为微信限制多图文只能显示一条
        //$weatherArray[] = array("Title" =>$weather['currentCity']."天气预报", "Description" =>"", "PicUrl" =>"", "Url" =>"");
        for ($i = 0; $i < count($weather["weather_data"]); $i++) {
            $weatherArray[] = array(
                "Title"=>$weather['currentCity'],
                "Description"=>
                $weather["weather_data"][$i]["date"]."\n".
                $weather["weather_data"][$i]["weather"]." ".
                $weather["weather_data"][$i]["wind"]." ".
                $weather["weather_data"][$i]["temperature"],

                "PicUrl"=>(($curHour >= 6) && ($curHour < 18))?$weather["weather_data"][$i]["dayPictureUrl"]:$weather["weather_data"][$i]["nightPictureUrl"], "Url"=>"");
        }
        return $weatherArray;
    }



}

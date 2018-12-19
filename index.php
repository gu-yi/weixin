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

    /**
     * 文本消息
     */
    private function text($postObj){
        $keyword = trim($postObj->Content);
        if (strstr($keyword,'时间')){
            $content = '现在时间:'.date("Y-m-d H:i:s",time());
        }elseif (strstr($keyword,'图片')){
            $content = array('MediaId'=>'qoLsYfF_GEaVrPHJXrsYLvfj-nqS5DRuxoJ4KMFedYb-w--44hkB0Lb9rSVFm7pS');
        }else{
            $content = '这是一个文本消息';
        }
        if(is_array($content)){
            $res = $this->r_image($postObj,$content);
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

}

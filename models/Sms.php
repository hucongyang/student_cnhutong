<?php

/**
 * 大汉三通短信接口
 * Class Sms
 */
class Sms extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function postSms($phone, $code)
    {
        $account = "dh21317";       // 账号
        $password = "64524df26b97a865accddfa4e8b3b684";         // 密码
//        $phone = "152******23";     // 号码
        $content = "您好，您的验证码为$code";           // 内容
        $sign = "";                     // 短信签名
        $sendTime = "";                 // 发送时间

        $sendSmsAddress = "http://3tong.net/http/sms/Submit";
        $message ="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            ."<message>"
            . "<account>"
            . $account
            . "</account><password>"
            . $password
            . "</password>"
            . "<msgid></msgid><phones>"
            . $phone
            . "</phones><content>"
            . $content
            . "</content><subcode>"
            ."</subcode><sign>"
            . $sign
            . "</sign><sendtime>"
            . $sendTime
            . "</sendtime>"
            ."</message>";

        $params = array(
            'message' => $message);

        $data = http_build_query($params);
        $context = array('http' => array(
            'method' => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $data,
        ));
        $contents = file_get_contents($sendSmsAddress, false, stream_context_create($context));

        return $contents;

    }
}
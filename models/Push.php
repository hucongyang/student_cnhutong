<?php
require_once("../extensions/JPush/JPush.php");


class Push extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    /**
     * @param $app_id
     * @param $user_id
     * @param $msg_content
     * @param $msg_title
     * @return bool
     */
    public function pushMsg($app_id, $user_id, $msg_content, $msg_title)
    {
        if ($app_id == 10) {
            // 学员端jPush
            $app_key = Yii::app()->params['student_JPush']['app_key'];
            $masterSecret = Yii::app()->params['student_JPush']['master_secret'];
        } elseif ($app_id == 11) {
            // 教师端jPush
            $app_key = Yii::app()->params['teacher_JPush']['app_key'];
            $masterSecret = Yii::app()->params['teacher_JPush']['master_secret'];
        } else {
            return false;
        }

        // 调用jPush API
        $client = new JPush($app_key, $masterSecret);
        $result = $client->push()
            ->setPlatform(array('ios', 'android'))
            ->addAlias($user_id)
            ->addTag('all')
            ->setNotificationAlert('Hi, JPushnihao')
            ->setMessage($msg_content, $msg_title)
            ->setOptions(100000, 3600, null, false)
            ->send();

        $message="";//存储推送状态
        if($result){
            $res_arr = json_encode($result, true);
            if(isset($res_arr['error'])){                       //如果返回了error则证明失败
                echo $res_arr['error']['message'];          //错误信息
                $error_code=$res_arr['error']['code'];             //错误码
                switch ($error_code) {
                    case 200:
                        $message= '发送成功！';
                        break;
                    case 1000:
                        $message= '失败(系统内部错误)';
                        break;
                    case 1001:
                        $message = '失败(只支持 HTTP Post 方法，不支持 Get 方法)';
                        break;
                    case 1002:
                        $message= '失败(缺少了必须的参数)';
                        break;
                    case 1003:
                        $message= '失败(参数值不合法)';
                        break;
                    case 1004:
                        $message= '失败(验证失败)';
                        break;
                    case 1005:
                        $message= '失败(消息体太大)';
                        break;
                    case 1008:
                        $message= '失败(appkey参数非法)';
                        break;
                    case 1020:
                        $message= '失败(只支持 HTTPS 请求)';
                        break;
                    case 1030:
                        $message= '失败(内部服务超时)';
                        break;
                    default:
                        $message= '失败(返回其他状态，目前不清楚额，请联系开发人员！)';
                        break;
                }
            }else{
                $message="发送成功！";
            }
        }else{      //接口调用失败或无响应
            $message='接口调用失败或无响应';
        }
        echo  "推送信息:{$message}";

    }
}
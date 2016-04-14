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
     * @param $msg_type
     * @param $msg_title
     * @param $alert_content
     * @return bool
     */
    public function pushMsg($app_id, $user_id, $msg_type, $msg_title, $alert_content)
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

        $result = array();
        try {
            // 调用jPush API
            $client = new JPush($app_key, $masterSecret);
            $result = $client->push()
                ->setPlatform(array('ios', 'android'))
                ->addAlias($user_id)
                ->addTag('all')
                ->addAndroidNotification($alert_content, $msg_title, 1, array("msg_type" => $msg_type, "msg_title" => $msg_title))
                ->addIosNotification($alert_content, $msg_title, '+1', true, 'iOS category', array("msg_type" => $msg_type, "msg_title" => $msg_title))
                ->setOptions(100000, 3600, null, false)
                ->send();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }

//        $message="";    //存储推送状态
//        if($result){
//            $res_arr = json_encode($result, true);
//            if(isset($res_arr['error'])){                       //如果返回了error则证明失败
//                $message  = $res_arr['error']['message'];          //错误信息
//                $error_code     = $res_arr['error']['code'];             //错误码
//                self::insertPush($error_code, $message);
//            }else{
//                $message="发送成功！";
//                $error_code = 1111;
//                self::insertPush($error_code, $message);
//            }
//        }else{      //接口调用失败或无响应
//            $message = '接口调用失败或无响应';
//            $error_code = 0000;
//            self::insertPush($error_code, $message);
//        }
        return true;
    }

    /**
     * 记录jPush推送反馈信息
     * @param $error_code
     * @param $message
     * @return bool
     */
    public function insertPush($error_code, $message)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()->insert('com_push',
                array(
                    'error_code'        => $error_code,
                    'message'           => $message
                )
            );

        } catch (Exception $e) {
            error_log($e);
            return false;
        }

    }
}
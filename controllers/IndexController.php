<?php
require_once("../extensions/JPush/JPush.php");
/**
 * IndexController 默认控制器
 */
class IndexController extends Controller
{
    public function actionIndex()
    {
        $this->renderPartial('index');
    }

    public function actionJson()
    {
        $arr = array(
            'a' => 256996,
            'b' => 0,
            'c' => 429587,
            'd' => 2
        );
        var_dump($arr);
        $json = json_encode($arr);
        var_dump($json);

        $lesson = '{[{"lessonStudentId":"256996","step":"0"},{"lessonStudentId":"429587","step":"2"}]';
        var_dump($lesson);
        $lessonJson = json_decode($lesson);
        var_dump($lessonJson);
    }

    public function actionJPush()
    {
        $client = new JPush(Yii::app()->params['student_JPush']['app_key'], Yii::app()->params['student_JPush']['master_secret']);
        $result = $client->push()
            ->setPlatform(array('ios', 'android'))
            ->addAlias('alias1')
            ->addTag('all')
            ->setNotificationAlert('Hi, JPushnihao')
            ->addAndroidNotification('Hi, android notification', 'notification title', 1, array("key1"=>"value1", "key2"=>"value2"))
            ->addIosNotification("Hi, iOS notification", 'iOS sound', JPush::DISABLE_BADGE, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
            ->setMessage("msg content", 'msg title', 'type', array("key1"=>"value1", "key2"=>"value2"))
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
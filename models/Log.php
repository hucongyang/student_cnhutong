<?php

/**
 * Class Log 用户相关日志模型
 */
class Log extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    // 用户log
    private $log_code = array(
        // log 对应编号
        'USER_LOGIN'                        => 2101,            // 用户手机号,密码登录
        'AUTO_LOGIN'                        => 2102,            // 用户id,token自动登录
        'USER_GETUSERINFO'                  => 2103,            // 获取个人信息
        'USER_GETCODE'                      => 2104,            // 获取手机验证码
        'USER_CHECKCODE'                    => 2105,            // 验证获取的验证码
        'USER_REGISTER'                     => 2106,            // 用户注册
        'USER_RESETPASSWORD'                => 2107,            // 用户重置密码
        'USER_LOGOUT'                       => 2108,            // 退出帐号
        'USER_BINDMEMBER'                   => 2109,            // 用户绑定学员信息

        'LESSON_GETSTUDENTSCHEDULE'         => 2201,            // 获取日历课程
        'LESSON_GETLESSONDETAILS'           => 2201,            // 获取课时详情

        'STUDENT_GETSTUDENTINFO'            => 2301,            // 获取学员详细信息
    );

    /**
     * 用户登录行为log
     * @param $user_id
     * @return bool
     */
    public function user_log($user_id)
    {
        $con_log = Yii::app()->cnhutong;
        $table_name = sprintf('com_log_user_login_history_%s', date('Ym'));
        try {
            $con_log->createCommand()->insert($table_name,
                    array(
                        'user_id'                   => $user_id,
                        'login_ip'                  => $GLOBALS['__IP'],
                        'version'                   => $GLOBALS['__VERSION'],
                        'device_id'                 => $GLOBALS['__DEVICE_ID'],
                        'platform'                  => $GLOBALS['__PLATFORM'],
                        'channel'                   => $GLOBALS['__CHANNEL'],
                        'app_version'               => $GLOBALS['__APP_VERSION'],
                        'os_version'                => $GLOBALS['__OS_VERSION'],
                        'app_id'                    => $GLOBALS['__APP_ID'],
                        'login_time'                => date('Y-m-d H:i:s')
                    )
                );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 用户操作行为log
     * @param $user_id
     * @param $action_id
     * @param $params
     * @return bool
     */
    public function action_log($user_id, $action_id, $params)
    {
        $con_log = Yii::app()->cnhutong;
        $table_name = sprintf('com_log_user_action_%s', date('Ym'));
        try {
            $con_log->createCommand()->insert($table_name,
                    array(
                        'user_id'                   => $user_id,
                        'action_id'                 => $action_id,
                        'params'                    => $params,
                        'create_ts'                 => date('Y-m-d H:i:s')
                    )
                );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }
}
<?php

/**
 * Class User 用户个人中心相关模型
 */
class User extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 根据用户名/手机号码, 获得用户的user_id
     * @param null $username
     * @param $mobile
     * @return bool|int
     */
    public function getUserId($username = null, $mobile)
    {
        $user_id = 0;
        if ($username != null || $mobile != null)
        {
            $con_user = Yii::app()->cnhutong;
            $param = array();
            $condition = null;
            if ($username) {
                $condition = 'username = :username';
                $param[':username'] = $username;
            } elseif ($mobile) {
                $condition = 'mobile = :mobile';
                $param[':mobile'] = $mobile;
            }

            $condition = $condition . ' AND status = :status';
            $param[':status'] = 1;

            try {
                $user_id = $con_user->createCommand()
                    ->select('id')
                    ->from('com_user')
                    ->where($condition, $param)
                    ->queryScalar();
            } catch (Exception $e) {
                error_log($e);
                return false;
            }
        }
        return $user_id;
    }

    /**
     * 根据ID获得用户信息
     * @param $user_id
     * @return bool
     */
    public function getUserInfo($user_id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user';
            $user_info = $con_user->createCommand()
                ->select('id, mobile, username, password, score')
                ->from($table_name)
                ->where('id = :user_id AND status = :status', array(':user_id' => $user_id, ':status' => 1))
                ->queryRow();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $user_info;
    }

    /**
     * 用户表新增用户
     * @param $mobile
     * @param $username
     * @param $password
     * @param $register_time
     * @param $last_login_time
     * @param $score
     * @return int
     */
    public function insertUser($mobile, $username, $password, $register_time, $last_login_time, $score)
    {
        $user_id = 0;
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user';
            $con_user->createCommand()->insert($table_name,
                    array(
                        'mobile'            => $mobile,
                        'username'          => $username,
                        'password'          => $password,
                        'register_time'     => $register_time,
                        'last_login_time'   => $last_login_time,
                        'score'             => $score
                    )
                );

            $user_id = Yii::app()->cnhutong->getLastInsertID();

        } catch (Exception $e) {
            error_log($e);
        }
        return $user_id;
    }

    /**
     * 用户重置密码
     * @param $mobile
     * @param $password
     * @return bool
     */
    public function resetPassword($mobile, $password)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user';
            $con_user->createCommand()->update($table_name,
                array(
                    'password'              => $password
                ),
                'mobile = :mobile',
                array(
                    ':mobile'               => $mobile
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }
}
<?php

/**
 * Class Token 用户token
 */
class Token extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function insertUserToken($user_id)
    {
        $time = date("Y-m-d H:i:s");
        // 创建用户token
        $token = md5($user_id . $time . microtime() . 'studentApp');
        $expire_ts = date("Y-m-d H:i:s", strtotime('+30 day'));
        try {
            $con_token = Yii::app()->cnhutong;
            $table_name = 'com_user_token';
            $con_token->createCommand()->insert($table_name,
                array(
                    'user_id'           => $user_id,
                    'token'             => $token,
                    'create_ts'         => $time,
                    'expire_ts'         => $expire_ts
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $token;
    }

    /**
     * 获取用户系统中的token值
     *
     * @param $user_id  int         -- 用户ID
     * @return string %token        -- 用户token
     */
    public function getUserToken($user_id)
    {
        try {
            $con_token = Yii::app()->cnhutong;
            $table_name = 'com_user_token';
            $token = $con_token->createCommand()
                ->select('token')
                ->from($table_name)
                ->where('user_id = :user_id', array(':user_id' => $user_id))
                ->queryScalar();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $token;
    }

    /**
     * 更新用户系统中的token值
     * @param $user_id          int          -- 用户ID
     * @return bool|string  $token
     */
    public function updateUserToken($user_id)
    {
        $time = date("Y-m-d H:i:s");
        $token = md5($user_id . $time .microtime() . 'studentApp');
        $expire_ts = date("Y-m-d H:i:s", strtotime('+30 day'));
        try {
            $con_token = Yii::app()->cnhutong;
            $table_name = 'com_user_token';
            $con_token->createCommand()->update($table_name,
                array(
                    'token' => $token,
                    'create_ts' => $time,
                    'expire_ts' => $expire_ts
                ),
                'user_id = :user_id',
                array(
                    ':user_id' => $user_id
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $token;
    }

    /**
     * 指定用户ID的token过期
     * @param $user_id      int         -- 用户
     * @return bool
     */
    public function expireToken($user_id)
    {
        $yesterday = date("Y-m-d H:i:s", mktime(time() - 86400));
        $time      = date("Y-m-d H:i:s");
        try {
            $con_token = Yii::app()->cnhutong;
            $table_name = 'com_user_token';
            $result = $con_token->createCommand()->update($table_name,
                array(
                    'create_ts' => $time,
                    'expire_ts' => $yesterday
                ),
                'user_id = :user_id',
                array(
                    ':user_id' => $user_id
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }

    /**
     * 验证token
     * @param $user_id      int         -- 用户ID
     * @param $token
     * @return bool
     */
    public function verifyToken($user_id, $token)
    {
        try {
            $con_token = Yii::app()->cnhutong;
            $table_name = 'com_user_token';
            $result = $con_token->createCommand()
                ->select('token, expire_ts')
                ->from($table_name)
                ->where('user_id = :user_id', array(':user_id' => $user_id))
                ->queryRow();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }

        if (is_array($result)) {
            if ( ($result['token'] == (string)$token) && strtotime($result['expire_ts']) >= time() ) {
                return true;
            }
        }

        return false;
    }
}
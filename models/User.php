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
        if ($username != null || $mobile != null) {
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
                    'mobile' => $mobile,
                    'username' => $username,
                    'password' => $password,
                    'register_time' => $register_time,
                    'last_login_time' => $last_login_time,
                    'score' => $score
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
                    'password' => $password
                ),
                'mobile = :mobile',
                array(
                    ':mobile' => $mobile
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 更新最后登录时间
     * @param $user_id
     * @param $last_login_time
     * @return bool
     */
    public function updateLastLoginTime($user_id, $last_login_time)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user';
            $con_user->createCommand()->update($table_name,
                array(
                    'last_login_time' => $last_login_time
                ),
                'id = :user_id',
                array(
                    ':user_id' => $user_id
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 验证口令/手机号码对应的用户是否存在,存在则口令可以使用,不存在则口令不可使用
     * @param $salt
     * @param $mobile
     * @return bool
     */
    public function verifySaltMobile($salt, $mobile)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'ht_member';
            $id = $con_user->createCommand()
                ->select('id')
                ->from($table_name)
                ->where('salt = :salt AND mobile = :mobile', array(':salt' => $salt, ':mobile' => $mobile))
                ->queryScalar();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $id;
    }

    /**
     * 根据APP ID和学员ID得到两者是否存在绑定关系
     * @param $user_id
     * @param $memberId
     * @return bool
     */
    public function existUserIdMemberId($user_id, $memberId)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user_member';
            $id = $con_user->createCommand()
                ->select('id')
                ->from($table_name)
                ->where('user_id = :userId AND member_id = :memberId AND status = 1',
                    array(
                        ':userId' => $user_id,
                        ':memberId' => $memberId
                    )
                )
                ->queryScalar();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $id;
    }

    /**
     * 绑定APP ID 和 memberId 关系,插入数据
     * @param $user_id
     * @param $memberId
     * @param $status
     * @param $create_ts
     * @return bool
     */
    public function insertUserIdMemberId($user_id, $memberId, $status, $create_ts)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user_member';
            $con_user->createCommand()->insert($table_name,
                array(
                    'user_id' => $user_id,
                    'member_id' => $memberId,
                    'status' => $status,
                    'create_ts' => $create_ts
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 根据APP ID 获得该ID绑定的学员ID数量
     * @param $user_id
     * @return bool|int
     */
    public function bindMemberNum($user_id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_user_member';
            $count = $con_user->createCommand()
                ->select('count(id)')
                ->from($table_name)
                ->where('user_id = :userId AND status = 1', array(':userId' => $user_id))
                ->queryscalar();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $count;
    }

    /**
     * 获得绑定的学员信息
     * @param $user_id
     * @return array|bool
     */
    public function getMembers($user_id)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()
                ->select('cm.id as id, cm.member_id as member_id, m.name as name')
                ->from('com_user_member cm')
                ->leftJoin('ht_member m', 'cm.member_id = m.id')
                ->where('cm.user_id = :userId AND status = 1', array(':userId' => $user_id))
                ->queryAll();

            if (!$result) {
                return $data;
            } else {
                $member = array();
                foreach ($result as $row) {
                    $member['memberId'] = $row['member_id'];
                    $member['memberName'] = $row['name'];
                    $data[] = $member;
                }
            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
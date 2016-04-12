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
    public function updateLastLoginTime($user_id, $last_login_time = null)
    {
        if ($last_login_time == null) {
            $last_login_time = date("Y-m-d H:i:s");
        }

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

    /**
     * 用户提交留言信息接口
     * @param $user_id
     * @param $teacherId
     * @param $content
     * @return array|bool
     */
    public function postMessage($user_id, $teacherId, $content)
    {
        $dateTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_app_message';
            $data = $con_user->createCommand()->insert($table_name,
                array(
                    'teacher_id'            => $teacherId,
                    'student_id'            => $user_id,
                    'admin_id'              => $user_id,
                    'date_time'             => $dateTime,
                    'content'               => $content,
                    'status'                => 0
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 用户查看留言列表接口
     * @param $user_id
     * @return array|bool
     */
    public function myMessageList($user_id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()
                ->select('c.id AS messageId, c.student_id AS studentId, m.name AS studentName,
                c.date_time AS dateTime, c.content AS content, c.status')
                ->from('com_app_message c')
                ->leftjoin('ht_member m', 'c.student_id = m.id')
                ->where('c.student_id = :user_id', array(':user_id' => $user_id))
                ->group('c.teacher_id')
                ->order('c.id desc')
                ->queryAll();
            $data = $result;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 用户查看留言详情接口
     * @param $user_id
     * @param $teacherId
     * @param $messageId
     * @return bool
     */
    public function myMessageDetail($user_id, $teacherId, $messageId)
    {
        if ($messageId == 0) {
            $where = null;
        } else {
            $where = " AND c.id < $messageId ";
        }

        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT
                    c.id AS messageId, c.student_id AS studentId, c.date_time AS dateTime,
                    c.content AS content, c.teacher_id AS teacherId, c.admin_id AS adminId, m.name, c.status
                    FROM com_app_message c
                    LEFT JOIN ht_member m ON c.admin_id = m.id
                    WHERE ((c.admin_id = " . $user_id . " AND c.teacher_id = " . $teacherId . ")
                    OR (c.admin_id = " . $teacherId . " AND c.student_id = " . $user_id . "))
                    " . $where . "
                    ORDER BY c.date_time DESC LIMIT 5";
            $commend = $con_user->createCommand($sql)->queryAll();

            $message = array();
            $data = array();
            foreach ($commend as $row) {
                self::updateAppMessageStatus($row['messageId']);      // 改变留言状态
                $message['messageId']               = $row['messageId'];
                if ($row['adminId'] == $row['teacherId']) {
                    unset($message['studentId']);
                    unset($message['studentName']);
                    $message['teacherName']         = $row['name'];
                    $message['teacherId']           = $row['teacherId'];
                } else if ($row['adminId'] == $row['studentId']) {
                    unset($message['teacherId']);
                    unset($message['teacherName']);
                    $message['studentId']           = $row['studentId'];
                    $message['studentName']         = $row['name'];
                }
                $message['dateTime']                = $row['dateTime'];
                $message['content']                 = $row['content'];
                $data['messageDetails'][]    = $message;
            }

        } catch (Exception $e) {
            error_log($e);
            var_dump($e);
            return false;
        }
        return $data;
    }

    /**
     * 用户查看留言详情接口后,留言未读信息状态status改为1（已读）
     * @param $messageId
     * @return bool
     */
    public function updateAppMessageStatus($messageId)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_app_message';
            $data = $con_user->createCommand()->update($table_name,
                array(
                    'status'                => 1
                ),
                'id = :messageId',
                array(
                    ':messageId' => $messageId
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }


    /**
     * 用户投诉信息接口
     * @param $user_id
     * @param $departmentId
     * @param $name
     * @param $reason
     * @param $flag
     * @return bool
     */
    public function myComplaint($user_id, $departmentId, $name, $reason, $flag)
    {
        $nowTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_feedback';
            $data = $con_user->createCommand()->insert($table_name,
                array(
                    'user_id'           => $user_id,
                    'department_id'     => $departmentId,
                    'name'              => $name,
                    'reason'            => $reason,
                    'flag'              => $flag,
                    'create_ts'         => $nowTime
                )
            );

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 消息状态接口
     * @param $user_id
     * @return bool
     */
    public function getNoticeFlag($user_id)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            // 获得符合条件的消息
            $sql = "SELECT n.id as id, type
                    FROM com_notice n
                    LEFT JOIN com_user_member cum ON n.accept_id = cum.member_id
                    WHERE user_id = " . $user_id ." and n.status = 0
                    GROUP BY type
                    ";
            $result = $con_user->createCommand($sql)->queryAll();

//            $types  = array_column($result, 'type');
            $types = array();
            foreach ($result as $row) {
                $types[] = $row['type'];
            }

            // 新消息状态
            if ($types) {
                $data['noticeFlag']     = 1;

                // 课程消息状态
                if (in_array(1, $types)) {
                    $data['lessonFlag']     = 1;
                } else {
                    $data['lessonFlag']     = 0;
                }

                // 请假消息状态
                if (in_array(2, $types)) {
                    $data['leaveFlag']     = 1;
                } else {
                    $data['leaveFlag']     = 0;
                }

                // 补课消息状态
                if (in_array(3, $types)) {
                    $data['extraFlag']     = 1;
                } else {
                    $data['extraFlag']     = 0;
                }

            } else {
                $data['noticeFlag']     = 0;
                $data['lessonFlag']     = 0;
                $data['leaveFlag']      = 0;
                $data['extraFlag']      = 0;
            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }


    /**
     * 用户意见反馈接口
     * @param $user_id
     * @param $reason
     * @param $flag
     * @return bool
     */
    public function postFeedBack($user_id, $reason, $flag)
    {
        $data = array();
        $nowTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_feedback';
            $con_user->createCommand()->insert($table_name,
                array(
                    'user_id'           => $user_id,
                    'reason'            => $reason,
                    'flag'              => $flag,
                    'create_ts'         => $nowTime,
                    'status'            => 1
                )
            );

            $data['feedBackId']         = Yii::app()->cnhutong->getLastInsertId();
            $data['reason']             = $reason;
            $data['createTime']         = $nowTime;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 用户意见反馈列表
     * @param $user_id
     * @param $page
     * @return bool
     */
    public function feedBackList($user_id, $page)
    {
        try {

            $page = $page * 10;
            $pageLimit = "limit $page, 10";

            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT id AS feedBackId, reason, create_ts AS createTime
                    FROM com_feedback
                    WHERE user_id = " . $user_id . " AND flag = 2 AND `status` = 1
                    ORDER BY create_ts DESC
                    " . $pageLimit . "
                    ";

            $result = $con_user->createCommand($sql)->queryAll();
            $data['feedBacks']  = $result;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
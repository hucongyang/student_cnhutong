<?php

/**
 * Class User 有关用户数据模型
 */
class User extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取用户ID getUserId
     *
     * @param $username             -- 用户名
     * @return int  $user_id        --
     * 说明：根据用户名，获取用户的user_id
     */
    public function getUserId($username)
    {
        $user_id = 0;
        if ($username != null)
        {
            $con_user = Yii::app()->cnhutong;
            if (!$username)
            {
                return $user_id;
            }
            $user_id = 0;
            try {
                $user_id = $con_user->createCommand()
                    ->select('id')
                    ->from('ht_member')
                    ->where('username = :username', array(':username' => $username))
                    ->queryScalar();
            } catch (Exception $e) {
                error_log($e);
            }
        }
        return $user_id;
    }

    /**
     * 获取用户信息 getUserSafeInfo
     * @param $user_id int              -- 用户ID
     * @return array|bool               -- 用户信息
     */
    public function getUserInfo($user_id)
    {
        $user_id = intval($user_id);

        $data = array();
        $table_name = 'ht_member';
        try {
            $con_user = Yii::app()->cnhutong;
            $data = $con_user->createCommand()
                ->select('id, username, password, name, gender, department_id, title, mobile, email, token')
                ->from($table_name)
                ->where('id = :id', array(':id' => $user_id))
                ->queryRow();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 用户登录后获取个人中心详细信息
     * @param $user_id
     * @return array|bool
     */
    public function getUserDetailInfo($user_id)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT
                    m.`name` AS teacherName, m.departments_managed AS departmentManaged
                    FROM ht_member m
                    WHERE m.id = '" . $user_id . "' ";
            $command = $con_user->createCommand($sql)->queryRow();

            // 获得该教师的校区信息
//            $command['departments'] = self::getDepartmentsById($command['departmentManaged']);
            $departments = self::getDepartmentsById($command['departmentManaged']);
            if ($departments) {
                $command['departments'] = $departments;
            } else {
                $command['departments'] = array();
            }

            // 获得该教师的课程信息
//            $command['subjects'] = self::getSubjectsByUserId($user_id);
            $subjects = self::getSubjectsByUserId($user_id);
            if ($subjects) {
                $command['subjects'] = $subjects;
            } else {
                $command['subjects'] = array();
            }

            // 释放 departmentManaged
            unset($command['departmentManaged']);
            $data = $command;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 根据教师的 ht_member department_managed 获取教师的校区信息
     * @param $department_managed
     * @return array|bool
     */
    public function getDepartmentsById($department_managed)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT d.id, d.`name` AS departmentName
                    FROM ht_department d
                    WHERE d.id IN (" . $department_managed .") ";
            $command = $con_user->createCommand($sql)->queryAll();
            $result = array();
            foreach ($command as $row) {
                $result['departmentId']             = $row['id'];
                $result['departmentName']           = $row['departmentName'];
                $data[] = $result;
            }
//            var_dump($sql);
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 根据教师ID 获得教师课程数目
     * @param $user_id
     * @return array|bool
     */
    public function getSubjectsByUserId($user_id)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT c.subject_id AS subjectId, c.`subject` as subjectName
                    FROM ht_lesson_student s
                    LEFT JOIN ht_course c on s.course_id=c.id
                    WHERE s.step >= 0
                    AND s.teacher_id = " . $user_id . "
                    GROUP BY c.subject_id";
            $command = $con_user->createCommand($sql)->queryAll();
            $result = array();
            foreach ($command as $row) {
                $result['subjects']                 = $row['subjectId'];
                $result['subjectName']              = $row['subjectName'];
                $data[] = $result;
            }
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 教师查看工资信心接口:
     * 月份默认为当前月份（$date）到月初（1号）的工资
     * @param $user_id
     * @param $date
     * @param $departmentId
     * @return array|bool
     */
    public function myReword($user_id, $date, $departmentId)
    {
        $data = array();
        try {

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 教师提交留言信息接口
     * @param $user_id
     * @param $studentId
     * @param $content
     * @return array|bool
     */
    public function postMessage($user_id, $studentId, $content)
    {
        $dateTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_app_message';
            $data = $con_user->createCommand()->insert($table_name,
                array(
                    'teacher_id'            => $user_id,
                    'student_id'            => $studentId,
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
     * 教师查看留言列表接口
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
                ->where('c.admin_id = :user_id', array(':user_id' => $user_id))
                ->group('c.student_id')
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
     * 教师查看留言详情接口
     * @param $user_id
     * @param $studentId
     * @param $messageId
     * @return bool
     */
    public function myMessageDetail($user_id, $studentId, $messageId)
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
                    WHERE ((c.admin_id = " . $user_id . " AND c.student_id = " . $studentId . ")
                    OR (c.admin_id = " . $studentId . " AND c.teacher_id = " . $user_id . "))
                    " . $where . "
                    ORDER BY c.date_time DESC LIMIT 5";
            $commend = $con_user->createCommand($sql)->queryAll();

            $message = array();
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
            return false;
        }
        return $data;
    }

    /**
     * 教师查看留言详情接口后,留言未读信息状态status改为1（已读）
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
     * 教师投诉信息接口
     * @return bool
     */
    public function myComplaint()
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = '';
            $data = $con_user->createCommand()->insert($table_name,
                array(
                    ''
                )
            );

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
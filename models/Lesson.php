<?php

/**
 * Class Lesson 课程模型
 */
class Lesson extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 判断数组里面是否有字符串元素,要求全是整数
     * @param $user_id
     * @param $member
     * @return bool
     */
    public function isIntMember($user_id, $member)
    {
        if (is_array($member)) {
            foreach ($member as $k => $v) {
                if (!is_numeric($v)) {
                    return false;
                }

                if (!User::model()->existUserIdMemberId($user_id, $v)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 日历课程接口:
     * 根据月份日期获取学员具体课时详情（课程表）,默认为当前日期课程
     * @param $member
     * @param $year
     * @param $month
     * @param $day
     * @param $date
     * @return array|bool
     */
    public function getStudentSchedule($member, $year, $month, $day, $date)
    {
        $data = array();
        $member = implode(",", $member);
        $dateTime = strtotime($date);
        $nextDate = date("Y-m", strtotime('+1 month', $dateTime));     // 当前年月的下一月
        $nowDate = date("Y-m", strtotime($date));                   // 当前年月
        try {
            $con_lesson = Yii::app()->cnhutong;
            // 日历课程,每日签到状态
            $sql1 = "SELECT
                    t1.date as lessonDate, min(t1.status_id) as lessonStatus
                    FROM ht_lesson_student AS t1
                    WHERE student_id IN (" . $member . ") AND t1.step>=0 and  t1.step not in(4,5,6)
                    AND t1.status_id != 5
                    AND t1.date < '" . $nextDate . "-" . 01 . "'
                    AND t1.date > '" . $nowDate . "-" . 01 . "'
                    GROUP BY t1.date";
            $command1 = $con_lesson->createCommand($sql1)->queryAll();
            $data['lessons'] = $command1;
            // 日历课程,具体日期课时状态
            $sql2 = "SELECT
                    a.id AS lessonStudentId, a.date AS lessonDate, a.time AS lessonTime,
                    a.step as step, s.id AS stujectId, s.title AS subjectName,
                    a.department_id AS departmentId, d.name AS departmentName,
                    a.teacher_id AS teacherId, c.name AS teacherName,
                    a.student_id AS memberId, b.name AS memberName
                    FROM ht_lesson_student a
                    LEFT JOIN ht_member b ON a.student_id=b.id
                    LEFT JOIN ht_member c ON a.teacher_id=c.id
                    LEFT JOIN ht_department d ON a.department_id=d.id
                    LEFT JOIN ht_course e ON a.course_id=e.id
                    LEFT JOIN ht_subject s ON e.subject_id = s.id
                    WHERE a.step>=0 and a.step not in(4,5)
                    AND a.status_id NOT IN (5)
                    AND a.student_id IN (" . $member . ")
                    AND date = '" . $year . "-" . $month . "-" . $day . "'
                    ORDER BY date,time";
            $command2 = $con_lesson->createCommand($sql2)->queryAll();
            $data['lessonDay'] = $command2;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 获取课时详情接口
     * @param $lessonStudentId
     * @return bool
     */
    public function getLessonDetails($lessonStudentId)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT
                      a.date AS lessonDate, a.time AS lessonTime,
                      a.step,
                      e.id AS courseId, e.course AS courseName,
                      a.department_id AS departmentId, d.name AS departmentName,
                      a.teacher_id AS teacherId, c.name AS teacherName,
                      a.student_id AS memberId, b.name AS memberName,
                      ifnull(a.lesson_content, '') AS lessonContent,
                      IFNULL(a.teacher_rating, '') AS teacherGrade, a.teacher_comment AS teacherEval,
                      IFNULL(a.student_rating, '') AS studentGrade, a.student_comment AS studentEval
                    FROM ht_lesson_student a
                      LEFT JOIN ht_member b ON a.student_id=b.id
                      LEFT JOIN ht_member c ON a.teacher_id=c.id
                      LEFT JOIN ht_department d ON a.department_id=d.id
                      LEFT JOIN ht_course e ON a.course_id=e.id
                    WHERE a.step>=0 and a.step not in(4,5)
                          AND a.status_id NOT IN (5)
                      AND a.id = " . $lessonStudentId ."
                    GROUP BY time
                    ORDER BY date,time";
            $command = $con_user->createCommand($sql)->queryRow();
            $data = $command;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 提交课时请假信息
     * @param $memberId
     * @param $courseId
     * @param $lessonStudentId
     * @param $dateTime
     * @param $reason
     * @return bool
     */
    public function lessonStudentLeave($memberId, $courseId, $lessonStudentId, $dateTime, $reason)
    {
        $nowTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;
            $table_name = 'com_leave';
            // 请假表记录请假详情
            $data = $con_user->createCommand()->insert($table_name,
                array(
                    'member_id'             => $memberId,
                    'course_id'             => $courseId,
                    'lesson_student_id'     => $lessonStudentId,
                    'leave_time'            => $dateTime,
                    'create_time'           => $nowTime,
                    'update_time'           => $nowTime,
                    'flag'                  => 2,
                    'status'                => 1,
                    'reason'                => $reason
                )
            );

            $leaveId = Yii::app()->cnhutong->getLastInsertID();

            // 学员请假直接修改课表课时状态
            $dataLeave = $con_user->createCommand()->update('ht_lesson_student',
                array(
                    'step' => 6,
                ),
                'id = :lessonStudentId',
                array(
                    ':lessonStudentId' => $lessonStudentId
                )
            );

            // 根据课时id获得学员id (ht_lesson_student),再根据学员id获得绑定的user_id（com_user_member）

            $lessonDetail           = Common::model()->getLessonDetailById($lessonStudentId);
            // 推送相关请假消息给相应老师

            // 学员名称
            $studentName            = $lessonDetail['studentName'];

            // 时间
            $dateTime               = $lessonDetail['date'] . ' ' . $lessonDetail['time'];

            // 课程
            $courseName             = $lessonDetail['course'];

            // 课时
            $lesson_cnt_charged     = $lessonDetail['lesson_cnt_charged'];

            // 校区
            $departmentName         = $lessonDetail['department'];

            // 老师
            $teacherId              = $lessonDetail['teacherId'];

            // 教室
            $classroomName          = $lessonDetail['classroom'];

            // 理由 备注 $extraReason

            $msg_content = " 学员: $studentName\n 时间: $dateTime\n 课程: $courseName\n 课时: $lesson_cnt_charged\n 教室: $departmentName/$classroomName\n 备注: $reason ";
            $msg_title = '学员请假通知';

            // 添加学员请假消息
            Notice::model()->insertNotice($memberId, $teacherId, 2, $leaveId, null, 2, $msg_title, $msg_content, $nowTime, 1, 0);

            $push = Push::model()->pushMsg(11, $teacherId, '2', $msg_title);
            if ($push) {
                return true;
            } else {
                return false;
            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }

    }


    /**
     * 根据课时id判断该课时是否已请过假
     * @param $lessonStudentId
     * @return bool
     */
    public function isExistLessonStudentId($lessonStudentId)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()
                ->select('id')
                ->from('com_leave')
                ->where('lesson_student_id = :lessonStudentId', array(':lessonStudentId' => $lessonStudentId))
                ->queryScalar();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }

    /**
     * 课时id是否存在
     * @param $lessonStudentId
     * @return bool
     */
    public function isExistId($lessonStudentId)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()
                ->select('id')
                ->from('ht_lesson_student')
                ->where('id = :id', array(':id' => $lessonStudentId))
                ->queryScalar();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }

    /**
     * 用户提交课时评价
     * @param $lessonStudentId
     * @param $studentGrade
     * @param $studentEval
     * @return bool
     */
    public function postLessonEval($lessonStudentId, $studentGrade, $studentEval)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()->update('ht_lesson_student',
                array(
                    'student_rating'        => $studentGrade,
                    'student_comment'       => $studentEval
                ),
                'id = :id',
                array(
                    ':id'                   => $lessonStudentId
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }
}
<?php

/**
 * Class Task 任务数据模型
 */
class Task extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 验证提交课程编号
     * @param $lessonJson
     * @return bool
     */
    public function verifyPost($lessonJson)
    {
        foreach ($lessonJson as $row) {
            if (!self::verifyTask($row['lessonStudentId'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证课时编号id是否可以提交签到签到和课程详情
     *
     * @param $lessonStudentId
     * @return bool
     */
    public function verifyTask($lessonStudentId)
    {
        $nowDate = date("Y-m-d");       // 当前日期
        $nowDate_1 = date("Y-m-d", strtotime("-1 day"));        // 当前日期减 1
        $nowTime = date("H:i");         // 当前时间
        $overTime = '15:00';            // 过期时间
        try {
            $con_task = Yii::app()->cnhutong;
            $sql = "SELECT a.date, a.time
                    FROM ht_lesson_student AS a
                    WHERE a.id = '" . $lessonStudentId . "'
                    ";
            $command = $con_task->createCommand($sql)->queryRow();
//            var_dump($sql);
            if ($command['date'] > $nowDate) {
                return false;
            } elseif ($command['date'] < $nowDate_1) {
                return false;
            } else {
//                if (substr($command['time'], -5) > $nowTime) {
//                    return false;
//                }
//                return true;
                // 昨天的课时签到限制
                if ( ($command['date'] == $nowDate_1) && ($nowTime < $overTime) ) {
                    return true;
                }

                // 今天的课时签到限制
                if ( ($command['date'] == $nowDate) && (substr($command['time'], -5) < $nowTime) ) {
                    return true;
                }

                return false;

            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 任务列表：
     * 教师上课后，若是未进行学员是否出勤的确认，将显示一条任务
     * （课时签到有时间限制，当天“15”时之后不再显示前一天签到任务）。
     * 任务显示课程的日期，科目，校区以及计划排课人数
     * @param $user_id
     * @return array|bool
     */
    public function getTaskList($user_id)
    {
        $date = date("Y-m-d");                              // 当前日期
        $yesterday = date("Y-m-d", strtotime("-1 day"));    // 当前日期前一天时间
        $time = date("H:i");                                // 当前小时分钟，用于判断课时结束没有
        $rHour = date("H");                                 // 当前小时，用户判断前一天课时任务是否显示
        $data = array();
        try {
            $con_task = Yii::app()->cnhutong;
            // 获得任务列表
            // 前一天
            $sql1 = "SELECT
                    a.date AS lessonDate, a.time AS lessonTime,
                    s.title as subjectName,
                    a.department_id AS departmentId, d.name AS departmentName,
                    count(a.id) AS studentNum
                    FROM ht_lesson_student a
                    LEFT JOIN  ht_member b ON a.student_id=b.id
                    LEFT JOIN ht_member c ON a.teacher_id=c.id
                    LEFT JOIN ht_department d ON a.department_id=d.id
                    LEFT JOIN ht_course e ON a.course_id=e.id
                    LEFT JOIN ht_subject s ON e.subject_id = s.id
                    WHERE a.step>=0 and a.step not in(4,5)
                    AND a.status_id NOT IN (1)
                    AND a.teacher_id = " . $user_id . "
                    AND date = '" . $yesterday . "'
                    group by time
                    order by date,time";
            $command1 = $con_task->createCommand($sql1)->queryAll();
            // 今天
            $sql2 = "SELECT
                    a.date AS lessonDate, a.time AS lessonTime,
                    s.title as subjectName,
                    a.department_id AS departmentId, d.name AS departmentName,
                    count(a.id) AS studentNum
                    FROM ht_lesson_student a
                    LEFT JOIN  ht_member b ON a.student_id=b.id
                    LEFT JOIN ht_member c ON a.teacher_id=c.id
                    LEFT JOIN ht_department d ON a.department_id=d.id
                    LEFT JOIN ht_course e ON a.course_id=e.id
                    LEFT JOIN ht_subject s ON e.subject_id = s.id
                    WHERE a.step>=0 and a.step not in(4,5)
                    AND a.status_id NOT IN (1)
                    AND a.teacher_id = " . $user_id . "
                    AND date = '" . $date . "'
                    group by time
                    order by date,time";
            $command2 = $con_task->createCommand($sql2)->queryAll();

            if ($rHour >= 15) {                 // 时间限制,当前日期时间 15时 以后不显示前一天的课时任务
                $data['yesterday'] = array();
            } else {
                $data['yesterday'] = $command1;
            }
//            $data[" $date "] = $command2;
            // 未到时间的课时不显示
            $result = array();
            $merge = array();
            if ($command2) {

                foreach ($command2 as $row) {
                    if (substr($row['lessonTime'], -5) < $time) {
                        $result['lessonDate'] = $row['lessonDate'];
                        $result['lessonTime'] = $row['lessonTime'];
                        $result['subjectName'] = $row['subjectName'];
                        $result['departmentId'] = $row['departmentId'];
                        $result['departmentName'] = $row['departmentName'];
                        $result['studentNum'] = $row['studentNum'];
                        $merge[] = $result;
//                    $data[" $date "][] = $result;
//                } else {
//                    $data[" $date "] = $result;
//                }else {
//                    $merge[] = $result;
                    }
//                $data['task'] = array_merge($data['task'], $merge);
                    $data['today'] = $merge;
                }

            } else {
                $data['today'] = array();
            }


        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 任务签到：
     * 点击单条任务信息，获得该课时下上课学员信息
     * @param $user_id
     * @param $lessonDate
     * @param $lessonTime
     * @return array|bool
     */
    public function getSign($user_id, $lessonDate, $lessonTime)
    {
        $data = array();
        try {
            $con_task = Yii::app()->cnhutong;
            // 获得任务签到
            $sql = "SELECT
                    a.id AS lessonStudentId, a.student_id AS studenId, b.`name` AS studentName, a.step
                    FROM ht_lesson_student AS a
                    LEFT JOIN ht_member b ON a.student_id = b.id
                    WHERE a.step >= 0 AND a.step NOT IN (4,5)
                    AND a.teacher_id = " . $user_id ."
                    AND a.date = '" . $lessonDate . "'
                    AND a.time = '" . $lessonTime . "'
                    AND a.status_id NOT IN (2, 4)
                    order by a.student_id";
            $command = $con_task->createCommand($sql)->queryAll();
            $data['lessonStudents'] = $command;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 提交任务签到接口:
     * 教师在App中提交学员课时签到信息
     * @param $lessonJson
     * @return bool|int
     */
    public function postSign($lessonJson)
    {
        $command = 1;
        try {
            $con_task = Yii::app()->cnhutong;
            $table_name = 'ht_lesson_student';
            // 按照课时ID进行签到,ht_lesson_student: status_id = 1(老师签到),step = 0 正常| step = 2 缺勤
            foreach ($lessonJson as $row) {
                // 需要对$lessonJson里面的数值做判断 if...
                $result = $con_task->createCommand()->update($table_name,
                    array(
                        'status_id' => 1,
                        'step' => $row['step']
                    ),
                    'id = :id',
                    array(
                        ':id' => $row['lessonStudentId']
                    )
                );
            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $command;
    }

    /**
     * 获得任务课时详情接口:
     * 获取教师某课时学员信息以及课时内容课时评价
     * @param $user_id
     * @param $lessonDate
     * @param $lessonTime
     * @return array|bool
     */
    public function getLessonDetails($user_id, $lessonDate, $lessonTime)
    {
        $data = array();
        try {
            $con_task = Yii::app()->cnhutong;
            // 获得任务课时详情
            $sql = "SELECT
                    a.id AS lessonStudentId, a.student_id AS studentId, m.`name` AS studentName, a.step AS step,
                    IFNULL(lt.topic, '') AS lessonContent, IFNULL(a.lesson_content, '') AS modifyContent,
                    IFNULL(a.student_rating, '') AS studentGrade, a.student_comment AS studentEval,
                    IFNULL(a.teacher_rating, '') AS teacherGrade, a.teacher_comment AS teacherEval
                    FROM ht_lesson_student AS a
                    LEFT JOIN ht_member m ON m.id = a.student_id
                    LEFT JOIN ht_lesson_student_topic lst ON lst.lesson_student_id = a.id
                    LEFT JOIN ht_lesson_topics lt ON lt.id = lst.lesson_topic_id
                    WHERE a.step >= 0 AND a.step NOT IN (4,5)
                    AND a.teacher_id = " . $user_id . "
                    AND a.date = '" . $lessonDate . "'
                    AND a.time = '" . $lessonTime . "'
                    AND a.status_id NOT IN (2, 4)
                    order by a.student_id";
            $command = $con_task->createCommand($sql)->queryAll();
            $data['lessonDetails'] = $command;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 提交任务课时详情内容接口
     * 教师在APP中提交学员课时详情内容信息
     * @param $lessonJson
     * @return bool|int
     */
    public function postLessonDetail($lessonJson)
    {
        $now = date("Y-m-d H:i:s");
        $command = 1;
        try {
            $con_task = Yii::app()->cnhutong;
            $table_name = 'ht_lesson_student';
            // 提交任务课时详情
            foreach ($lessonJson as $row) {
                $result = $con_task->createCommand()->update($table_name,
                    array(
                        'lesson_content' => $row['modifyContent'],
                        'teacher_rating' => $row['teacherGrade'],
                        'teacher_comment'=> $row['teacherEval']
                    ),
                    'id = :id',
                    array(
                        ':id' => $row['lessonStudentId']
                    )
                );
            }
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $command;
    }
}
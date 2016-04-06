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
                    a.id AS lessonStudentId, a.student_id AS studentId, b.`name` AS studentName, a.step
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
     * @param $user_id
     * @param $lessonJson
     * @return bool|int
     */
    public function postSign($user_id, $lessonJson)
    {
        $nowTime = date('Y-m-d H:m:i');
        try {
            $con_task = Yii::app()->cnhutong;
            $table_name = 'ht_lesson_student';
            // 按照课时ID进行签到,ht_lesson_student: status_id = 1(老师签到),step = 0 正常, step = 1 补课, step = 2 缺勤, step = 6 请假
            foreach ($lessonJson as $row) {
                // 需要对$lessonJson里面的数值做判断 if... step = 0 增加一条消息记录,jPush推送学员用户; step = 2|6 增加一条补课机会记录
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

                if ($row['step'] == 2 || $row['step'] == 6)
                {
                    // 判断 step = 2|6 添加补课机会记录
                    self::insertExtraChance($row['lessonStudentId'], $row['step']);
                } elseif ($row['step'] == 0) {
                    // step = 0 增加一条消息记录,jPush推送学员用户发送销课通知
                    $acceptIdArr = self::getAcceptIdByLessonStudentId($row['lessonStudentId']);

                    // $acceptArr = array(); 则表示为该学员未绑定任何user_id 只记录消息，不推送
                    if ($acceptIdArr && $acceptIdArr[0]['user_id']) {

                        foreach ($acceptIdArr as $acceptId) {
                            $lessonDetail           = Common::model()->getLessonDetailById($row['lessonStudentId']);
                            // 推送相关补课消息给相应老师

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
                            $teacherName            = Common::model()->getNameById($user_id);

                            // 教室
                            $classroomName          = $lessonDetail['classroom'];

                            // 理由 备注 $extraReason

                            $msg_content = " 学员: $studentName &8424 时间: $dateTime &8424 课程: $courseName &8424 课时: $lesson_cnt_charged &8424 老师: $teacherName &8424 教室: $departmentName/$classroomName ";
                            $msg_title = '销课通知';

                            // 添加老师销课消息
                            Notice::model()->insertNotice($user_id, $acceptId['user_id'], 1, null, null, 3, $msg_title, $msg_content, 1, 0);

                            $push = Push::model()->pushMsg(10, $acceptId['user_id'], '1', $msg_title);
                            if ($push) {
                                return true;
                            } else {
                                return false;
                            }

                        }
                    } else {
                        // 记录消息,不推送
                        $lessonDetail           = Common::model()->getLessonDetailById($row['lessonStudentId']);
                        // 推送相关补课消息给相应老师

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
                        $teacherName            = Common::model()->getNameById($user_id);

                        // 教室
                        $classroomName          = $lessonDetail['classroom'];

                        // 学员
                        $studentId              = $lessonDetail['studentId'];

                        // 理由 备注 $extraReason

                        $msg_content = " 学员: $studentName &8424 时间: $dateTime &8424 课程: $courseName &8424 课时: $lesson_cnt_charged &8424 老师: $teacherName &8424 教室: $departmentName/$classroomName ";
                        $msg_title = '销课通知';

                        // 添加老师销课消息
                        Notice::model()->insertNotice($user_id, $studentId, 1, null, null, 3, $msg_title, $msg_content, $nowTime, 1, 0);
                    }


                } else {
                    return false;
                }
                return true;        // 不加会返回null
            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }

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

    /**
     * 老师签学员缺勤或请假,在补课机会表里面产生一条补课机会记录
     * @param $lessonStudentId
     * @param $step
     * @return bool
     */
    public function insertExtraChance($lessonStudentId, $step)
    {
        $nowTime = date('Y-m-d H:m:i');
        try {
            $con_task = Yii::app()->cnhutong;
            // 获取对应 lessonStudentId 的课堂信息
            $resultSelect = $con_task->createCommand()
                ->select('student_id, course_id, classroom_id, lesson_cnt_charged, date, time')
                ->from('ht_lesson_student')
                ->where('id = :lessonStudentId', array(':lessonStudentId' => $lessonStudentId))
                ->limit('1')
                ->queryRow();

            $endTime = ($resultSelect['date'] . ' ' . $resultSelect['time']);
            $resultInsert = $con_task->createCommand()->insert('com_extra_chance',
                    array(
                        'member_id'             => $resultSelect['student_id'],
                        'lesson_student_id'     => $lessonStudentId,
                        'course_id'             => $resultSelect['course_id'],
                        'classroom_id'          => $resultSelect['classroom_id'],
                        'step'                  => $step,
                        'end_time'              => $endTime,
                        'create_time'           => $nowTime,
                        'create_user_id'        => 0,
                        'update_time'        => $nowTime,
                        'update_user_id'        => 0,
                        'flag'                  => 0,
                        'lesson_cnt_charged'    => $resultSelect['lesson_cnt_charged']
                    )
                );

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 根据课时id获得学员id (ht_lesson_student),再根据学员id获得绑定的user_id（com_user_member）
     * @param $lessonStudentId
     * @return array
     */
    public function getAcceptIdByLessonStudentId($lessonStudentId)
    {
        try {
            $con_task = Yii::app()->cnhutong;
            $acceptId = $con_task->createCommand()
                ->select('cum.user_id as user_id')
                ->from('ht_lesson_student ls')
                ->leftJoin('com_user_member cum', 'ls.student_id = cum.member_id')
                ->where('ls.id = :lessonStudentId', array(':lessonStudentId' => $lessonStudentId))
                ->queryAll();

            if ($acceptId) {
                $data = $acceptId;
            } else {
                $data = array();
            }

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
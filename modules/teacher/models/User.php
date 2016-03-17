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

            // 获得该教师的科目信息
            $courses = self::getCourseByUserId($user_id);
            if ($courses) {
                $command['courses'] = $courses;
            } else {
                $command['courses'] = array();
            }

            // 获得该教师的教室信息
            $classrooms = self::getClassroomByDepartment($command['departmentManaged']);
            if ($classrooms) {
                $command['classrooms'] = $classrooms;
            } else {
                $command['classrooms'] = array();
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
                $result['subjectId']                 = $row['subjectId'];
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
     * 根据教师ID 获得教师科目
     * @param $user_id
     * @return array|bool
     */
    public function getCourseByUserId($user_id)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT ls.course_id AS courseId, c.course AS courseName,
                           s.id AS subjectId, s.title AS subjectName
                    FROM ht_lesson_student AS ls
                    LEFT JOIN ht_course AS c ON ls.course_id = c.id
                    LEFT JOIN ht_subject AS s ON c.subject_id = s.id
                    WHERE ls.teacher_id = " . $user_id . " GROUP BY course_id";
            $command = $con_user->createCommand($sql)->queryAll();
            $result = array();
            foreach ($command as $row) {
                $result['courseId']                 = $row['courseId'];
                $result['courseName']               = $row['courseName'];
                $result['subjectId']                = $row['subjectId'];
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
     * 根据校区id获得教室列表
     * @param $department_managed
     * @return array|bool
     */
    public function getClassroomByDepartment($department_managed)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT c.id AS classroomId, c.department_id as departmentId,
                    d.name AS departmentName, c.name AS classroomName
                    FROM ht_classroom AS c
                    LEFT JOIN ht_department AS d ON c.department_id = d.id
                    WHERE c.department_id in (" . $department_managed .") AND c.step >= 0";
            $command = $con_user->createCommand($sql)->queryAll();
            $result = array();
            foreach ($command as $row) {
                $result['classroomId']                 = $row['classroomId'];
                $result['classroomName']               = $row['classroomName'];
                $result['departmentId']                = $row['departmentId'];
                $result['departmentName']              = $row['departmentName'];
                $data[] = $result;
            }
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 教师查看工资信息接口:
     * 月份默认为当前月份（$date）到月初（1号）的工资
     * @param $user_id
     * @param $date
     * @param $departmentId
     * @return array|bool
     */
    public function myReward($user_id, $date, $departmentId)
    {
        $dateStr = $date."-01";
        $dateStart = date("Y-m-d", strtotime($dateStr));       // 当前月第一天
        $dateEnd = date("Y-m-d", strtotime("$dateStart +1 month -1 day"));    // 当前月最后一天

        $data = array();
        try {
            $con_task = Yii::app()->cnhutong;
            // 获得任务列表
            // 前一天
            $sql1 = "SELECT m.id as teacher_id, m.name AS mname,b.name AS department, a.department_id, " .
                "SUM(a.lesson_cnt_charged) AS lesson_cnt_charged,SUM(a.lesson_price*a.lesson_cnt_charged) AS lesson_price, " .
                "AVG(CASE WHEN a.lesson_teacher_fee_rate>0 THEN a.lesson_teacher_fee_rate ELSE g.rate END) AS rate, " .
                "SUM(CASE WHEN a.lesson_teacher_fee>0 THEN a.lesson_teacher_fee ELSE a.lesson_cnt_charged*lesson_price*g.rate END) AS lesson_fee, " .
                "a.status_id,a.lesson_confirmed_time, CASE WHEN a.step=0 THEN '正常' WHEN a.step=1 THEN '补课' " .
                "WHEN a.step=2 THEN '缺勤' WHEN a.step=3 THEN '弃课' " .
                "WHEN a.step=6 THEN '请假' WHEN a.step=4 THEN '冻结' WHEN a.step=5 THEN '退费' WHEN a.step=7 THEN '顺延补课' " .
                "WHEN a.step=8 THEN '补课后弃课' ELSE '其他' END step, " .
                "a.step AS step_id, sum(case when TO_DAYS(lesson_confirmed_time) - TO_DAYS(date)>36 then " .
                "CASE WHEN a.lesson_teacher_fee>0 THEN a.lesson_teacher_fee ELSE a.lesson_cnt_charged*lesson_price*g.rate END else 0 end) as over_delayed_lesson_fee, " .
                "sum(case when TO_DAYS(lesson_confirmed_time) - TO_DAYS(date)>36 then lesson_cnt_charged else 0 end) as over_delayed_lesson_cnt, " .
                "sum(case when TO_DAYS(lesson_confirmed_time) - TO_DAYS(date)>8 then CASE WHEN a.lesson_teacher_fee>0 THEN a.lesson_teacher_fee ELSE " .
                "a.lesson_cnt_charged*lesson_price*g.rate END else 0 end) as delayed_lesson_fee,sum(case when (TO_DAYS(lesson_confirmed_time) - TO_DAYS(date)>8) " .
                "and (TO_DAYS(lesson_confirmed_time) - TO_DAYS(date)<=36) then lesson_cnt_charged else 0 end) as delayed_lesson_cnt " .
                "FROM ht_lesson_salary_rate g,ht_lesson_student a LEFT JOIN ht_member AS m ON a.teacher_id=m.id " .
                "LEFT JOIN ht_department b ON a.department_id=b.id LEFT JOIN ht_course d ON a.`course_id`=d.`id` " .
                "WHERE d.subject_id=g.subject_id	#AND share_scope>0 " .
                "AND d.id=a.course_id AND a.lesson_range=g.lesson_range AND a.department_id = ".$departmentId." " .
                "AND a.teacher_id=" . $user_id . " " .
                "AND DATE>='".$dateStart."' AND DATE <='".$dateEnd."' AND a.step>=0 AND a.step NOT IN(4,5) AND a.status_id NOT IN(5) " .
                "group by a.department_id, a.status_id, a.step,a.teacher_id";
            $query = $con_task->createCommand($sql1)->queryAll();

            $sumNum = array();

            $sumNum["lesson_fee_hr_ge61"] = 0;
            $sumNum["lesson_cnt_hr_ge61"] = 0;
            $sumNum["lesson_fee_hr_60"] = 0;
            $sumNum["lesson_cnt_hr_60"] = 0;
            $sumNum["lesson_fee_made"] = 0;
            $sumNum["lesson_cnt_charged_made"] = 0;
            $sumNum["delayedLessonFee"] = 0;
            $sumNum["delayedLessonCnt"] = 0;
            $sumNum["lesson_fee"] = 0;
            $sumNum["lesson_cnt_charged"] = 0;

            foreach($query as $key=>$lesson){

                if(($lesson["step_id"]==0 || $lesson["step_id"]==1)&&$lesson["status_id"]!=5){

                    $sumNum["lesson_cnt_charged"] += $lesson["lesson_cnt_charged"];
                    $sumNum["lesson_fee"] += $lesson["lesson_fee"];

                    $sumNum["delayedLessonCnt"] += $lesson["delayed_lesson_cnt"];
                    $sumNum["delayedLessonFee"] += $lesson["delayed_lesson_fee"];
                    $sumNum["overDelayedLessonCnt"] += $lesson["over_delayed_lesson_cnt"];
                    $sumNum["overDelayedLessonFee"] += $lesson["over_delayed_lesson_fee"];

                    if($lesson["status_id"]==50 ||$lesson["status_id"]==60 || $lesson["status_id"]==61 || $lesson["status_id"]==62){
                        $sumNum["lesson_cnt_charged_made"] += $lesson["lesson_cnt_charged"];
                        $sumNum["lesson_fee_made"] += $lesson["lesson_fee"];
                    }
                    if($lesson["status_id"]==60){
                        $sumNum["lesson_cnt_hr_60"] += $lesson["lesson_cnt_charged"];
                        $sumNum["lesson_fee_hr_60"] += $lesson["lesson_fee"];
                    }
                    if($lesson["status_id"]==61 || $lesson["status_id"]==62){
                        $sumNum["lesson_cnt_hr_ge61"] += $lesson["lesson_cnt_charged"];
                        $sumNum["lesson_fee_hr_ge61"] += $lesson["lesson_fee"];
                        $sumNum["lesson_fee_confirmed_hr_61"] += $lesson["lesson_fee"];
                        if($lesson["lesson_confirmed_time"]!="" && $lesson["lesson_confirmed_time"]!="null"){
                            $intervalDays = (strtotime(substr($lesson["lesson_confirmed_time"],0,10))-strtotime($lesson["date"]))/86400000;
                            if($intervalDays>36){
                                $sumNum["overDelayedLessonCnt_hr_61"] += $lesson["lesson_cnt_charged"];
                            }else if($intervalDays>8){
                                $sumNum["delayedLessonCnt_hr_61"] += $lesson["lesson_cnt_charged"];
                            }
                        }
                    }
                }
            }

            //已发放课时工资
            $data['hasSalary'] = sprintf("%.2f", $sumNum["lesson_fee_hr_ge61"]);
            //已发放课时数
            $data['hasCourse'] = $sumNum["lesson_cnt_hr_ge61"];
            //可发放课时工资
            $data['currentSalary'] = sprintf("%.2f", $sumNum["lesson_fee_hr_60"]);
            //可发放课时数
            $data['currentCourse'] = $sumNum["lesson_cnt_hr_60"];
            //课时收入
            $data['plannedSalary'] = sprintf("%.2f", $sumNum["lesson_fee_made"]);
            //完成课时数
            $data['plannedCourse'] = $sumNum["lesson_cnt_charged_made"];
            //延时调整
            $data['delaySalary'] = sprintf("%.2f", $sumNum["delayedLessonFee"]/2);
            //延时课时
            $data['delayCourse'] = $sumNum["delayedLessonCnt"];
            //排课课时收入
            $data['arrangeSalary'] = sprintf("%.2f", $sumNum["lesson_fee"]);
            //排课课时
            $data['arrangeCourse'] = $sumNum["lesson_cnt_charged"];

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
                ->where('c.teacher_id = :user_id', array(':user_id' => $user_id))
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
     * 教师获得补课学员信息
     * @param $user_id
     * @param $page
     * @param $courseId
     * @return array|bool
     */
    public function myExtraLessonStudentList($user_id, $page, $courseId)
    {
        $data = array();
        try {

            $page = $page * 10;
            $pageLimit = " limit $page, 10";

            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT cd.student_id AS studentId, m.name AS studentName
                    FROM ht_contract_detail AS cd
                    LEFT JOIN ht_contract AS c ON cd.contract_id = c.id
                    LEFT JOIN ht_member AS m ON cd.student_id = m.id
                    WHERE cd.teacher_id = " . $user_id ." AND cd.course_id = " . $courseId . "
                    AND c.end_date > now() " . $pageLimit . "
                    ";
            $command = $con_user->createCommand($sql)->queryAll();
            $data['extraStudents']  = $command;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 教师提交补课信息
     * @param $user_id
     * @param $departmentId
     * @param $courseId
     * @param $classroomId
     * @param $extraTime
     * @param $studentJson
     * @param $extraReason
     * @return array|bool
     */
    public function postExtraLesson($user_id, $departmentId, $courseId, $classroomId, $extraTime, $studentJson, $extraReason)
    {
        $data = array();
        $nowTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;

            // 补课表添加数据
            $result1 = $con_user->createCommand()->insert('com_extra',
                array(
                    'member_id'         => $user_id,
                    'extra_time'        => $extraTime,
                    'department_id'     => $departmentId,
                    'course_id'         => $courseId,
                    'classroom_id'      => $classroomId,
                    'create_time'       => $nowTime,
                    'update_time'       => $nowTime,
                    'flag'              => 1,
                    'status'            => 1,
                    'type'              => 2,
                    'reason'            => $extraReason
                )
            );
            // 获取补课表id
            $extraId = Yii::app()->cnhutong->getLastInsertID();


            // 补课详情表添加数据
            foreach ($studentJson as $row) {
                $result2 = $con_user->createCommand()->insert('com_extra_detail',
                    array(
                        'extra_id'          => $extraId,
                        'member_id'         => $row['studentId'],
                        'status'            => 0,
                        'create_time'       => $nowTime,
                        'update_time'       => $nowTime,
                        'create_user_id'    => 0,
                        'update_user_id'    => 0,
                    )
                );
            }


        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }


    /**
     * 教师查看补课列表
     * @param $user_id
     * @param $page
     * @param $status
     * @return array|bool
     */
    public function myExtraLessonList($user_id, $page, $status)
    {
        try {

            $page = $page * 10;
            $pageLimit = " limit $page, 10";

            if ($status == 0) {
                $extraStatus = "";
            } else {
                $extraStatus = "and ce.status = ". $status ." ";
            }

            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT ce.id AS extraId, ce.course_id AS courseId, c.course AS courseName,
                    ce.department_id AS departmentId, d.department AS departmentName,
                    ce.extra_time AS extraTime, ce.create_time AS createTime,
                    ce.status AS extraStatus, ce.type
                    FROM com_extra AS ce
                    LEFT JOIN ht_course AS c ON ce.course_id = c.id
                    LEFT JOIN ht_department d ON ce.department_id = d.id
                    WHERE ce.member_id = ". $user_id ."
                    " . $extraStatus ."
                    order by ce.create_time desc ". $pageLimit ." ";
            $command = $con_user->createCommand($sql)->queryAll();
            $data['extraLessons']  = $command;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 教师查看补课详情
     * @param $extraId
     * @return bool
     */
    public function myExtraLessonDetail($extraId)
    {
        try {

            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT ce.id AS extraId, ce.course_id AS courseId, c.course AS courseName,
                    ce.department_id AS departmentId, d.department AS departmentName,
                    ce.extra_time AS extraTime, ce.create_time AS createTime,
                    ce.status AS extraStatus, ce.type,
                    ce.classroom_id AS classroomId, class.name AS classroomName,
                    ce.member_id AS memberId, m.name AS memberName
                    FROM com_extra AS ce
                    LEFT JOIN ht_course AS c ON ce.course_id = c.id
                    LEFT JOIN ht_department d ON ce.department_id = d.id
                    LEFT JOIN ht_classroom class ON ce.classroom_id = class.id
                    LEFT JOIN ht_member m ON ce.member_id = m.id
                    WHERE ce.id = ". $extraId ."
                    order by ce.create_time desc ";
            $command = $con_user->createCommand($sql)->queryAll();
            $data['extraDetail']  = $command;

            $sqlStudent = "SELECT ed.member_id AS studentId, m.name AS studentName,
                           ed.status AS studentStatus, ed.update_time AS updateTime
                           FROM com_extra_detail ed
                           LEFT JOIN ht_member m ON ed.member_id = m.id
                           WHERE ed.extra_id = " . $extraId . " ";
            $commandStudent = $con_user->createCommand($sqlStudent)->queryAll();
            $data['extraStudent'] = $commandStudent;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
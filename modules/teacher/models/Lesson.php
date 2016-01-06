<?php

/**
 * 课程模型
 */
class Lesson extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 日历课程接口:
     * 根据月份日期获取教师具体课时详情（课程表）,默认为当前日期课程
     * @param $user_id
     * @param $year
     * @param $month
     * @param $day
     * @return array|bool
     */
    public function getSubjectSchedule($user_id, $year, $month, $day)
    {
        $data = array();
        try {
            $con_lesson = Yii::app()->cnhutong;
            // 日历课程,每日签到状态
            $sql1 = "SELECT
                    t1.date as lessonDate, min(t1.status_id) as lessonStatus
                    FROM ht_lesson_student AS t1
                    WHERE teacher_id = '" . $user_id . "' AND t1.step>=0 and  t1.step not in(4,5,6)
                    AND t1.status_id not in(5)
                    AND t1.date LIKE '" . $year . "-" . $month . "%" . "'
                    GROUP BY t1.date";
            $command1 = $con_lesson->createCommand($sql1)->queryAll();
            $data['lessons'] = $command1;
            // 日历课程,具体日期课时状态
            $sql2 = "SELECT
                    a.date AS lessonDate, a.time AS lessonTime,
                    min(a.status_id) as lessonStatus,
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
                    AND a.status_id NOT IN (5)
                    AND a.teacher_id = '" . $user_id . "'
                    AND date = '" . $year . "-" . $month . "-" . $day . "'
                    GROUP BY time
                    ORDER BY date,time";
            $command2 = $con_lesson->createCommand($sql2)->queryAll();
            $data['lessonDay'] = $command2;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
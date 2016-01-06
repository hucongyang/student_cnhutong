<?php
/**
 * 学员数据模型
 */
class Student extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取学员列表:
     * 默认显示该教师所授课程的所有学生,可以根据条件进行筛选搜索
     * @param $user_id
     * @param $search
     * @param $page
     * @return array
     */
    public function getAllStudents($user_id, $search, $page)
    {
        $data = array();
        try {
            $con_student = Yii::app()->cnhutong;


                $page = $page * 10;
                $pageLimit = " limit $page, 10";


            // 学员列表
//            $sql = "SELECT cd.student_id AS studentId, m.`name` AS studentName,
//                      c.subject_id AS subjectId, c.`subject` as subjectName,
//                      cd.department_id AS departmentId, d.`name` AS departmentName,
//                      sum(cd.lesson_finished_cnt) AS hasSerial, cd.lesson_cnt AS cotSerial,
//                      ifnull(max(cd.end_date), '') AS date
//                    FROM ht_contract_detail cd
//                      LEFT JOIN ht_member m ON m.id = cd.student_id
//                      LEFT JOIN ht_course c ON c.id = cd.course_id
//                      LEFT JOIN ht_department d ON d.id = cd.department_id
//                    WHERE cd.step >= 0 AND cd.teacher_id = '" . $user_id . "'
//                      AND m.`name` LIKE '" . "%" . $search . "%" . "'
//                      AND c.subject_id = " . $subjectId . "
//                      AND cd.department_id = " . $departmentId . "
//                      " . $studentIdWhere . "
//                    GROUP BY cd.student_id
//                    ORDER BY cd.student_id DESC
//                    LIMIT 10";

            // 新学员列表
            $sql = "select cd.student_id AS studentId, m.`name` as studentName
                    from ht_lesson_student cd
                      LEFT JOIN ht_member m on m.id = cd.student_id
                      LEFT JOIN ht_course c on c.id = cd.course_id
                      LEFT JOIN ht_department d on d.id = cd.department_id
                    where cd.step >= 0 and cd.teacher_id = " . $user_id . "
                    and m.`name` like '" . "%" . $search . "%" . "'
                    group by cd.student_id
                    order by cd.date desc " . $pageLimit . "
                    ";
            $command = $con_student->createCommand($sql)->queryAll();
//            var_dump($sql);
//            var_dump($command);
            $data['students'] = $command;
        } catch (Exception $e) {
            error_log($e);
        }
        return $data;
    }

    /**
     * 获取学员详情信息
     * @param $studentId
     * @return array|bool
     */
    public function getStudentInfo($studentId)
    {
        $data = array();
        $data1 = array();
        try {
            $con_student = Yii::app()->cnhutong;
            // 学员基本信息
            $sql1 = "SELECT m.id AS studentId, m.name AS studentName, m.birthday AS studentAge
                    FROM ht_member m
                    WHERE m.id = " . $studentId . "
                    ";
            $command1 = $con_student->createCommand($sql1)->queryAll();
            $result = array();
            foreach($command1 as $row) {
                $data['studentId']                    = $row['studentId'];
                $data['studentName']                  = $row['studentName'];
                $age = date('Y', strtotime("now")) - substr($row['studentAge'], 0, 4);
                if (empty($age)) {
                    $age = null;
                }
                $data['studentAge']                   = $age;
            }

            // 学员合同信息
            $sql2 = "select ct.id AS contractId, cd.id AS contractDetailId, ct.contract_serial AS contractSerialId,
                      cd.course_id AS courseId, ifnull(c.course, '') AS courseName,
                      cd.teacher_id AS teacherId, m.name AS teacherName,
                      cd.lesson_cnt AS cntLesson, ifnull(cd.lesson_finished_cnt, '') AS finishLesson,
                      ifnull(cd.start_date, '') AS startDate, ifnull(cd.end_date, '') AS endDate
                    from ht_contract ct LEFT JOIN ht_contract_detail cd on ct.id=cd.contract_id
                      LEFT JOIN ht_member m on m.id = cd.teacher_id
                      LEFT JOIN ht_course c on c.id = cd.course_id
                    where cd.step >= 0 and cd.student_id = " . $studentId . "
                    order by cd.create_time desc";
            $command2 = $con_student->createCommand($sql2)->queryAll();

            // 合并相同合同
//            $detailArr = array(array());
//            foreach($command2 as $row) {
//
//                $key = $row['contractSerialId'];
//                if(array_key_exists($key, $detailArr)) {
//                    array_push($detailArr[$key], $row);
//                } else {
//                    $detailArr[$key][] = $row;
//                }
//            }
//
//            $data['studentInfo']['contracts'] = array_filter($detailArr);

            $result2 = array();
            foreach ($command2 as $row) {
                $result2['contractSerialId']            = $row['contractSerialId'];
                if ($row['courseId'] == 0 || empty($row['courseId'])) {
                    $row['courseId'] = '';
                }
                $result2['courseId']                    = $row['courseId'];
                $result2['courseName']                  = $row['courseName'];
                $result2['teacherId']                   = $row['teacherId'];
                $result2['teacherName']                 = $row['teacherName'];
                $result2['cntLesson']                   = $row['cntLesson'];
                $result2['finishLesson']                = $row['finishLesson'];

                // 缺课课程
                $result2['lessLesson']                  = self::getLessLessonByContractDetailId($row['contractSerialId']);
                if (empty($result2['lessLesson'])) {
                    $result2['lessLesson'] = '';
                }
                $result2['startDate']                   = $row['startDate'];
                $result2['endDate']                   = $row['endDate'];

                $result2['status'] = 0;
                if ($row['cntLesson'] == $row['finishLesson']) {
                    $result2['status'] = 1;
                }

                $data['contracts'][]   =   $result2;
            }


        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 根据合同明细ID 获得补课课时
     * @param $contractDetailId
     * @return array|bool
     */
    public function getLessLessonByContractDetailId($contractDetailId)
    {
        $time = date('Y-m-d', strtotime("-1 day"));
        try {
            $con_student = Yii::app()->cnhutong;
            $sql = "select count(1)*2 as defilessoncnt
                    from ht_lesson_student s
                    where s.date < '" . $time . "'
                    and s.step not in(0, 1, 3, 8)
                    and s.contract_detail_id = " . $contractDetailId . "
                    ";
            $command = $con_student->createCommand($sql)->queryScalar();
            $data = $command;
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
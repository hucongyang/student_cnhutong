<?php

class Common extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取最新版本
     * @param $platform     平台
     * @param $app_id       应用编号
     * @return bool
     */
    public function updateVersion($platform, $app_id)
    {
        try {
            $com_common = Yii::app()->cnhutong;
            $table_name = 'com_channel';
            $result = $com_common->createCommand()
                ->select('last_version as lastVersion, new_version as newVersion, download as url,
                          create_ts as updateTime, content as updateContent')
                ->from($table_name)
                ->where('platform = :platform AND app_id = :app_id',
                    array(
                        ':platform'       => $platform,
                        ':app_id'         => $app_id
                    )
                )->order('id')
                ->limit('1')
                ->queryRow();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }

    /**
     * 根据学员id获得学员名称
     * @param $id
     * @return bool
     */
    public function getNameById($id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $name = $con_user->createCommand()
                ->select('name')
                ->from('ht_member')
                ->where('id = :studentId', array(':studentId' => $id))
                ->limit('1')
                ->queryScalar();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $name;
    }

    /**
     * 根据课程id获得课程名称
     * @param $id
     * @return bool
     */
    public function getCourseById($id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $course = $con_user->createCommand()
                ->select('course')
                ->from('ht_course')
                ->where('id = :id', array(':id' => $id))
                ->limit('1')
                ->queryScalar();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $course;
    }

    /**
     * 根据校区id获得校区名称
     * @param $id
     * @return bool
     */
    public function getDepartmentById($id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $department = $con_user->createCommand()
                ->select('name')
                ->from('ht_department')
                ->where('id = :id', array(':id' => $id))
                ->limit('1')
                ->queryScalar();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $department;
    }

    /**
     * 根据教室id获得教室名称
     * @param $id
     * @return bool
     */
    public function getClassroomById($id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $classroom = $con_user->createCommand()
                ->select('name')
                ->from('ht_classroom')
                ->where('id = :id', array(':id' => $id))
                ->limit('1')
                ->queryScalar();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $classroom;
    }

    /**
     * 根据课时id获得该课时相关详细信息
     * @param $lessonStudentId
     * @return array|bool
     */
    public function getLessonDetailById($lessonStudentId)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $detail = $con_user->createCommand()
                ->select('m.name as studentName, ls.date, ls.time, c.course, ls.lesson_cnt_charged,
                d.department, class.name as classroom, ls.teacher_id as teacherId, ls.student_id as studentId')
                ->from('ht_lesson_student ls')
                ->leftJoin('ht_member m', 'ls.student_id = m.id')
                ->leftJoin('ht_course c', 'ls.course_id = c.id')
                ->leftJoin('ht_department d', 'ls.department_id = d.id')
                ->leftJoin('ht_classroom class', 'ls.classroom_id = class.id')
                ->where('ls.id = :id', array(':id' => $lessonStudentId))
                ->limit('1')
                ->queryRow();

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $detail;
    }

    /**
     * 获取校区列表
     * @param $type
     * @return array|bool
     */
    public function getAllSchools($type)
    {
        if ($type == 1 ) {
            $areaType = '';
        } else {
            $areaType = "area = $type";
        }

        $data = array();
        try {

            $result = Yii::app()->cnhutong->createCommand()
                ->select('id as departmentId, area, name, telphone, address, picture as photo')
                ->from('com_department')
                ->where($areaType)
                ->queryAll();

            $data['schools'] = $result;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;

    }

    /**
     * @param $departmentId
     * @return array|bool
     */
    public function getSchoolInfo($departmentId)
    {
        $data = array();
        try {

            $result = Yii::app()->cnhutong->createCommand()
                ->select('area, name, telphone, address, bus_line as busLine, parking, point_x, point_y')
                ->from('com_department')
                ->where('id = :id', array(':id' => $departmentId))
                ->queryAll();

            $data['schoolInfo'] = $result;

            $resultPic = Yii::app()->cnhutong->createCommand()
                ->select('picture, desc')
                ->from('com_department_picture')
                ->where('department_id = :id', array(':id' => $departmentId))
                ->queryAll();

            $data['pictures'] = $resultPic;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;

    }

    /**
     * 获取活动列表
     * @return array|bool
     */
    public function getActivities()
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()
                ->select('id, title, img, url')
                ->from('com_activity')
                ->queryAll();

            $data['activities'] = $result;

        } catch (Exception $e) {
            error_log($e);
           return false;
        }
        return $data;
    }
}
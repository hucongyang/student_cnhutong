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
}
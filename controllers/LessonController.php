<?php

/**
 * Class LessonController 教学管理部分
 */
class LessonController extends ApiPublicController
{
    /**
     * 日历课程接口
     */
    public function actionGetStudentSchedule()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['memberIds']) || !isset($_REQUEST['date'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $memberIds = trim(Yii::app()->request->getParam('memberIds', null));
        $date = trim(Yii::app()->request->getParam('date', null));

        // 用户ID格式错误
        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_USER');
        }

        // 用户不存在，返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证格式是否正确
        $member = explode('|', $memberIds);
        if (!$member) {
            $this->_return('MSG_ERR_MEMBERS');
        }

        // 验证是否都是整数/是否与user_id具有绑定关系
        if (!Lesson::model()->isIntMember($user_id, $member)) {
            $this->_return('MSG_ERR_FAIL_MEMBER');
        }

        // memberId 超过规定人数 目前可以绑定4人
        if (count($member) > 4) {
            $this->_return('MSG_ERR_OVER_MEMBER_NUMBER');
        }

        // 验证日期格式是否合法
        if (!$this->isDate($date)) {
            $this->_return('MSG_ERR_FAIL_DATE_FORMAT');
        }

        $year = (mb_substr($date, 0, 4, 'utf8'));
        $month = (mb_substr($date, 5, 2, 'utf8'));
        $day = (mb_substr($date, 8, 2, 'utf8'));

        if (empty($year) || empty($month) || empty($day)) {
            $this->_return('MSG_ERR_FAIL_DATE_LESS');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 获取日历课程
            $data = Lesson::model()->getStudentSchedule($member, $year, $month, $day, $date);

            // 增加用户操作log
            $action_id = 2201;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id, $action_id, $params);

            $this->_return('MSG_SUCCESS', $data);

        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 获取课时详情
     */
    public function actionGetLessonDetails()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['lessonStudentId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $lessonStudentId = trim(Yii::app()->request->getParam('lessonStudentId', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户名不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (empty($lessonStudentId) || $lessonStudentId <= 0) {
            $this->_return('MSG_ERR_FAIL_LESSON_STUDENT_ID');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 获取学员详细信息
            $data = Lesson::model()->getLessonDetails($lessonStudentId);
            if (!$data) {
                $this->_return('MSG_NO_LESSON_STUDENT_ID');
            }

            // 增加用户操作log
            $action_id = 2202;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id, $action_id, $params);

            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }

    }

    /**
     *  用户提交课时请假信息
     */
    public function actionLessonStudentLeave()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['lessonStudentId']) || !isset($_REQUEST['memberId'])
            || !isset($_REQUEST['dateTime']) || !isset($_REQUEST['reason'])
            || !isset($_REQUEST['courseId']) ) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $lessonStudentId = trim(Yii::app()->request->getParam('lessonStudentId', null));
        $memberId = trim(Yii::app()->request->getParam('memberId', null));
        $dateTime = trim(Yii::app()->request->getParam('dateTime', null));
        $reason = trim(Yii::app()->request->getParam('reason', null));
        $courseId = trim(Yii::app()->request->getParam('courseId', null));

        // 用户名不存在,返回错误
        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (empty($lessonStudentId) || $lessonStudentId < 1) {
            $this->_return('MSG_ERR_FAIL_LESSON_STUDENT_ID');
        }

        if (Lesson::model()->isExistLessonStudentId($lessonStudentId)) {
            $this->_return('MSG_EXIST_LESSON_STUDENT_ID');
        }

        if (!ctype_digit($memberId) || $memberId < 1) {
            $this->_return('MSG_NO_MEMBER');
        }

        if (!ctype_digit($courseId) || $courseId < 1) {
            $this->_return('MSG_NO_MEMBER');
        }

        // 验证日期格式合法
        if (!$this->isDate($dateTime)) {
            $this->_return('MSG_ERR_FAIL_DATE_FORMAT');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {

            // 提交课时请假信息
            Lesson::model()->lessonStudentLeave($memberId, $courseId, $lessonStudentId, $dateTime, $reason);

            // 增加用户操作log
            $action_id = 2202;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id, $action_id, $params);

            $this->_return('MSG_SUCCESS');
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}
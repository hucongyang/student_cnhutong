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

        // 验证是否都是整数
        if (!Lesson::model()->isIntMember($member)) {
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

        $data = array();
        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 获取日历课程
            $data = Lesson::model()->getStudentSchedule($member, $year, $month, $day);
            $this->_return('MSG_SUCCESS', $data);

        } else {
            $this->_return('MSG_ERR_TOKEN');
        }


    }
}
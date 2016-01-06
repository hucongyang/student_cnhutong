<?php
/**
 * 教学管理部分
 */
class LessonController extends ApiPublicController
{
    /**
     * 日历课程接口
     */
    public function actionGetSubjectSchedule()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['date']))
        {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $date = trim(Yii::app()->request->getParam('date', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户名不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证日期格式合法
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
            $data = Lesson::model()->getSubjectSchedule($user_id, $year, $month, $day);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}
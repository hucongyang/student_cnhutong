<?php
/**
 * 学员管理部分
 */
class StudentController extends ApiPublicController
{
    /**
     * 获取学员列表
     */
    public function actionGetAllStudents()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['search']) || !isset($_REQUEST['page']) ) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $search = trim(Yii::app()->request->getParam('search', null));
        $page = trim(Yii::app()->request->getParam('page', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户名不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($page) && $page < 0) {
            $this->_return('MSG_ERR_FAIL_PAGE');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 获取学员列表,按条件进行搜索
            $data = Student::model()->getAllStudents($user_id, $search, $page);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 获取学员详细信息
     */
    public function actionGetStudentInfo()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['studentId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $studentId = trim(Yii::app()->request->getParam('studentId', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户名不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (empty($studentId) || $studentId <= 0) {
            $this->_return('MSG_ERR_FAIL_STUDENT');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 获取学员详细信息
            $data = Student::model()->getStudentInfo($studentId);
            if (!$data) {
                $this->_return('MSG_NO_STUDENT');
            }
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}
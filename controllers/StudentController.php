<?php
/**
 * 学员管理部分
 */
class StudentController extends ApiPublicController
{
    /**
     * 获取学员详细信息
     */
    public function actionGetStudentInfo()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['memberId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $memberId = trim(Yii::app()->request->getParam('memberId', null));

        // 用户ID格式错误
        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_USER');
        }

        // 用户不存在，返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (empty($memberId) || $memberId <= 0) {
            $this->_return('MSG_ERR_FAIL_STUDENT');
        }

        // 验证要添加的memberId是否和userId有绑定关系存在
        $existMemberId = User::model()->existUserIdMemberId($user_id, $memberId);
        if (!$existMemberId) {
            $this->_return('MSG_ERR_FAIL_MEMBER');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 获取学员详细信息
            $data = Student::model()->getStudentInfo($memberId);
            if (!$data) {
                $this->_return('MSG_NO_MEMBER');
            }
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}
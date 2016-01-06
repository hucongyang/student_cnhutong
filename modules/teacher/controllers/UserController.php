<?php
/**
 * UserController 个人中心控制器
 */
class UserController extends ApiPublicController
{
    /**
     * 用户登录接口
     * 用户使用用户名/密码登录
     */
    public function actionLogin()
    {
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $username = trim(Yii::app()->request->getParam('username'));
        $password = trim(Yii::app()->request->getParam('password'));

        $user_id = User::model()->getUserId($username);

        // 用户不存在，返回错误
        if ($user_id < 1)
        {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 获取用户信息
        $user_info = User::model()->getUserInfo($user_id);

        if ($username == $user_info['username']) {
            if ($password == '') {
                $this->_return('MSG_ERR_PASSWORD_WRONG');
            }
        }

        $data = array();
        if (strcmp($password, $user_info['password']) == 0) {
            // 返回token值
            $result_token = Token::model()->updateUserToken($user_id);
            $messageFlag  = 1;
            $noticeFlag   = 0;
            if ($result_token) {
                // 登录日志，修改token
                if ($user_info) {
                    $data['teacherId']          = $user_info['id'];
                    $data['token']              = $result_token;
                    $data['messageFlag']        = $messageFlag;
                    $data['noticeFlag']         = $noticeFlag;
                    $this->_return('MSG_SUCCESS', $data);
                } else {
                    $this->_return('MSG_ERR_UNKOWN');
                }
            } else {
                $this->_return('MSG_ERR_UNKOWN');
            }
        } else {
            // 密码错误
            $this->_return('MSG_ERR_PASSWORD_WRONG');
        }
    }

    /**
     * 自动登录接口
     * 用户使用用户ID/token验证登录，token 默认30天有效
     */
    public function actionAutoLogin()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId'));
        $token = trim(Yii::app()->request->getParam('token'));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在，返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        $data = array();
        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 返回token值
            $result_token = Token::model()->updateUserToken($user_id);
            $messageFlag  = 1;
            $noticeFlag   = 0;
            if ($result_token) {
                // 写入日志，更新用户信息
                $data['teacherId']          = $user_id;
                $data['token']              = $result_token;
                $data['messageFlag']        = $messageFlag;
                $data['noticeFlag']         = $noticeFlag;
                $this->_return('MSG_SUCCESS', $data);
            } else {
                $this->_return('MSG_ERR_UNKOWN');
            }
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 退出账号接口
     */
    public function actionLogout()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId'));
        $token = trim(Yii::app()->request->getParam('token'));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在，返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 指定token过期
            if (Token::model()->expireToken($user_id)) {
                // 退出不写LOG
                $this->_return('MSG_SUCCESS');
            }
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 留言/通知信息状态接口
     */
    public function actionGetMessageNoticeFlag()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        $data = array();
        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 留言/通知信息状态
            $messageFlag = 1;
            $noticeFlag = 0;
            $data['messageFlag'] = $messageFlag;
            $data['noticeFlag'] = $noticeFlag;
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 用户登录后获得个人中心详细信息
     */
    public function actionGetUserDetailInfo()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户名不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 个人详细信息
            $data = User::model()->getUserDetailInfo($user_id);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 教师查看工资接口
     */
    public function actionMyRecord()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['date']) || !isset($_REQUEST['departmentId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $date = trim(Yii::app()->request->getParam('date', null));
        $departmentId = trim(Yii::app()->request->getParam('departmentId', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 教师查看工资
            $data = User::model()->myReword($user_id, $date, $departmentId);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 教师提交留言信息接口
     */
    public function actionPostMessage()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['studentId']) || !isset($_REQUEST['content'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $studentId = trim(Yii::app()->request->getParam('studentId'));
        $content = trim(Yii::app()->request->getParam('content', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($studentId) || $studentId < 0 || empty($studentId)) {
            $this->_return('MSG_ERR_FAIL_STUDENT');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 添加留言操作
            User::model()->postMessage($user_id, $studentId, $content);
            $this->_return('MSG_SUCCESS');
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 教师查看留言详情接口
     */
    public function actionMyMessageList()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 教师查看留言信息列表
            $data = User::model()->myMessageList($user_id);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 教师查看留言详情接口
     */
    public function actionMyMessageDetail()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['studentId']) || !isset($_REQUEST['messageId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $studentId = trim(Yii::app()->request->getParam('studentId', null));
        $messageId = trim(Yii::app()->request->getParam('messageId', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($studentId) || $studentId < 0 || empty($studentId)) {
            $this->_return('MSG_ERR_FAIL_STUDENT');
        }

        if (!ctype_digit($messageId) || $messageId < 0) {
            $this->_return('MSG_ERR_FAIL_MESSAGE');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 教师查看留言详情
            $data = User::model()->myMessageDetail($user_id, $studentId, $messageId);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 教师投诉信息
     */
    public function actionMyComplaint()
    {
        if (!isset($_REQUEST['teacherId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['departmentName']) || !isset($_REQUEST['name'])
        || !isset($_REQUEST['reason'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('teacherId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $departmentName = trim(Yii::app()->request->getParam('departmentName', null));
        $name = trim(Yii::app()->request->getParam('name', null));
        $reason = trim(Yii::app()->request->getParam('reason', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户名不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (empty($departmentName) || !preg_match("/^[\x7f-\xff]+$/", $departmentName)) {
            $this->_return('MSG_ERR_FAIL_DEPARTMENT');
        }

        if (empty($name) || !preg_match("/^[\x7f-\xff]+$/", $name)) {
            $this->_return('MSG_ERR_FAIL_NAME');
        }

        if (empty($reason)) {
            $this->_return('MSG_ERR_FAIL_REASON');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 教师投诉/举手信息
            $data = '1';
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}
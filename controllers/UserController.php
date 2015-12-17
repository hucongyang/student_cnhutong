<?php

/**
 * Class UserController 个人中心控制器
 */
class UserController extends ApiPublicController
{
    /**
     *  获取手机验证码
     */
    public function actionGetCode()
    {
        if (!isset($_REQUEST['mobile']) || !isset($_REQUEST['codeType'])
        || empty($_REQUEST['mobile']) || empty($_REQUEST['codeType'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $mobile = trim(Yii::app()->request->getParam('mobile', null));
        $codeType = trim(Yii::app()->request->getParam('codeType', null));

        // 非法的手机号码
        if (!$this->isMobile($mobile)) {
            $this->_return('MSG_ERR_FAIL_MOBILE');
        }

        $codeArr = array(1, 2, 3);

        // 非法的验证码类型
        if (!in_array($codeType, $codeArr)) {
            $this->_return('MSG_ERR_FAIL_CODE_TYPE');
        }

        // 获取user_id
        $user_id = User::model()->getUserId(false, $mobile);

        // 生成验证码
        $code = Code::model()->produceCode(6, 0, 9);

        $nowTime = date("Y-m-d H:i:s");                                 // 当前系统时间
        $overTime = date("Y-m-d H:i:s", strtotime("+30 minutes"));       // 验证码有效期1分钟,过期时间;测试阶段30分钟
        $status = 0;                                                    // 默认是发送了验证码

        // 分类讨论:
        // codeType = 1 表示注册, =2 表示找回密码, =3 表示绑定帐号
        if ($codeType == 1) {

            if ($user_id) {
                $this->_return('MSG_ERR_INVALID_MOBILE');       // 该手机号码已被注册
            }
            Code::model()->insertCode($mobile, $code, $nowTime, $overTime, $status, 1);

        } elseif ($codeType == 2) {

            if (!$user_id) {
                $this->_return('MSG_ERR_UN_REGISTER_MOBILE');   // 该手机号码未注册,请先注册
            }
            Code::model()->insertCode($mobile, $code, $nowTime, $overTime, $status, 2);

        } elseif ($codeType == 3) {

            Code::model()->insertCode($mobile, $code, $nowTime, $overTime, $status, 3);

        } else {
            $this->_return('MSG_ERR_UNKOWN');
        }

        $this->_return('MSG_SUCCESS', $code);

    }

    /**
     *  验证获取的验证码
     */
    public function actionCheckCode()
    {
        if (!isset($_REQUEST['mobile']) || !isset($_REQUEST['code'])
        || !isset($_REQUEST['codeType']) || empty($_REQUEST['mobile'])
        || empty($_REQUEST['code']) || empty($_REQUEST['codeType'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $mobile = trim(Yii::app()->request->getParam('mobile', null));
        $code = trim(Yii::app()->request->getParam('code', null));
        $codeType = trim(Yii::app()->request->getParam('codeType', null));

        // 非法的手机号码
        if (!$this->isMobile($mobile)) {
            $this->_return('MSG_ERR_FAIL_MOBILE');
        }

        $codeArr = array(1, 2, 3);

        // 非法的验证码类型
        if (!in_array($codeType, $codeArr)) {
            $this->_return('MSG_ERR_FAIL_CODE_TYPE');
        }

        if (Code::model()->verifyCode($mobile, $code, $codeType)) {
            $this->_return('MSG_SUCCESS');
        } else {
            $this->_return('MSG_ERR_CODE');
        }

    }

    /**
     *  用户使用手机号/密码/验证码 注册新用户
     */
    public function actionRegister()
    {
        if (!isset($_REQUEST['mobile']) || !isset($_REQUEST['password'])
        || !isset($_REQUEST['code']) || empty($_REQUEST['mobile'])
        || empty($_REQUEST['password']) || empty($_REQUEST['code'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $mobile = trim(Yii::app()->request->getParam('mobile', null));
        $password = trim(Yii::app()->request->getParam('password', null));
        $code = trim(Yii::app()->request->getParam('code', null));

        // 非法的手机号码
        if (!$this->isMobile($mobile)) {
            $this->_return('MSG_ERR_FAIL_MOBILE');
        }

        // 获取user_id
        $user_id = User::model()->getUserId(false, $mobile);

        // 验证用户是否存在
        if ($user_id) {
            $this->_return('MSG_ERR_INVALID_MOBILE');       // 该手机号码已被注册
        }

        // 验证码是否过期
        if (!Code::model()->verifyCode($mobile, $code, 1)) {
            $this->_return('MSG_ERR_CODE');
        }

        // 密码不能与用户名相同
        if (strcmp($mobile, $password) == 0) {
            $this->_return('MSG_ERR_SET_SAME_PASSWORD');
        }

        // 注册用户

        $username = 'User' . $mobile;
        $register_time = date("Y-m-d H:i:s");
        $last_login_time = date("Y-m-d H:i:s");

        // 创建事务
        $user_transaction = Yii::app()->cnhutong->beginTransaction();
        try {
            // 生成APP唯一标识ID
            $user_id = User::model()->insertUser($mobile, $username, $password, $register_time, $last_login_time, 0);

            // 创建token
            Token::model()->insertUserToken($user_id);

            // 修改验证码使用状态
            Code::model()->updateCode($mobile, $code, 1);

            // 提交事务
            $user_transaction->commit();
        } catch (Exception $e) {
            error_log($e);
            $user_transaction->rollback();
            $this->_return('MSG_ERR_UNKOWN');
        }

        if($user_id <= 0) {
            $this->_return('MSG_ERR_UNKOWN');
        }

        $token = Token::model()->getUserToken($user_id);

        // 发送返回值
        $data = array();
        $data['userId']             = $user_id;
        $data['token']              = $token;

        $this->_return('MSG_SUCCESS', $data);
    }

    /**
     *  用户使用注册的用户名和密码登录
     */
    public function actionLogin()
    {
        if (!isset($_REQUEST['mobile']) || !isset($_REQUEST['password'])
        || empty($_REQUEST['mobile']) || empty($_REQUEST['password'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $mobile = trim(Yii::app()->request->getParam('mobile', null));
        $password = trim(Yii::app()->request->getParam('password', null));

        // 非法的手机号码
        if (!$this->isMobile($mobile)) {
            $this->_return('MSG_ERR_FAIL_MOBILE');
        }

        // 获取user_id
        $user_id = User::model()->getUserId(false, $mobile);

        // 验证用户是否存在
        if (!$user_id) {
            $this->_return('MSG_ERR_UN_REGISTER_MOBILE');          // 该手机号码未注册,请先注册
        }

        $user_info = User::model()->getUserInfo($user_id);

        if (strcmp($password, $user_info['password']) == 0) {
            // 返回更新的token
            $token = Token::model()->updateUserToken($user_id);
            if ($token) {
                $data['userId'] = $user_id;
                $data['token'] = $token;
                $this->_return('MSG_SUCCESS', $data);
            } else {
                $this->_return('MSG_ERR_UNKOWN');
            }
        } else {
            $this->_return('MSG_ERR_PASSWORD_WRONG');
        }

    }

    /**
     *  用户使用token,user_id 自动登录
     */
    public function actionAutoLogin()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
        || empty($_REQUEST['userId']) || empty($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));

        // 用户ID格式错误
        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_USER');
        }

        // 用户不存在，返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        $data = array();
        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 返回token值
            $token = Token::model()->updateUserToken($user_id);

            if ($token) {
                // 写入日志，更新用户信息
                $data['userId']             = $user_id;
                $data['token']              = $token;
                $this->_return('MSG_SUCCESS', $data);
            } else {
                $this->_return('MSG_ERR_UNKOWN');
            }
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }

    }

    /**
     * 用户重置密码
     */
    public function actionResetPassword()
    {
        if (!isset($_REQUEST['mobile']) || !isset($_REQUEST['password'])
        || !isset($_REQUEST['code']) || empty($_REQUEST['mobile'])
        || empty($_REQUEST['password']) || empty($_REQUEST['code'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $mobile = trim(Yii::app()->request->getParam('mobile', null));
        $password = trim(Yii::app()->request->getParam('password', null));
        $code = trim(Yii::app()->request->getParam('code', null));

        // 非法的手机号码
        if (!$this->isMobile($mobile)) {
            $this->_return('MSG_ERR_FAIL_MOBILE');
        }

        // 获取user_id
        $user_id = User::model()->getUserId(false, $mobile);

        // 验证用户是否存在
        if (!$user_id) {
            $this->_return('MSG_ERR_UN_REGISTER_MOBILE');   // 该手机号码未注册,请先注册
        }

        // 验证码是否过期
        if (!Code::model()->verifyCode($mobile, $code, 2)) {
            $this->_return('MSG_ERR_CODE');
        }

        // 创建事务
        $user_transaction = Yii::app()->cnhutong->beginTransaction();
        try {
            // 修改密码
            User::model()->resetPassword($mobile, $password);

            // 修改验证码使用状态
            Code::model()->updateCode($mobile, $code, 2);

            // 提交事务
            $user_transaction->commit();
        } catch (Exception $e) {
            error_log($e);
            $user_transaction->rollback();
            $this->_return('MSG_ERR_UNKOWN');
        }

        $this->_return('MSG_SUCCESS');

    }

    /**
     * 用户退出帐号
     */
    public function actionLogout()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId'));
        $token = trim(Yii::app()->request->getParam('token'));

        // 用户ID格式错误
        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_USER');
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
}

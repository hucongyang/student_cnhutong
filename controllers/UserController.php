<?php

/**
 * Class UserController 个人中心控制器
 */
class UserController extends ApiPublicController
{

    public function actionTest()
    {
        $string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            ."<response><msgid>2c92825934837c4d0134837dcba00150</msgid>"
            ."<result>0</result>"
            ."<desc>提交成功</desc>"
            ."<blacklist></blacklist>"
            ."</response>";

        $xml = simplexml_load_string($string);
        $result = $xml->result;
        if ($result == 0) {
            var_dump($result);
            echo "提交成功";
        } else {
            echo "失败";
        }
    }

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

        // 增加用户操作log
        $action_id = 2104;
        $params = '';
        foreach ($_REQUEST as $key => $value)  {
            $params .= $key . '=' . $value . '&';
        }
        $params = substr($params, 0, -1);
        Log::model()->action_log($user_id = 0, $action_id, $params);

        // 解析短信发送返回状态
//        $result = simplexml_load_string(Sms::model()->postSms($mobile, $code))->result;
//        if ($result == 0) {
//            $this->_return('MSG_SUCCESS', $code);
//        } else {
//            $this->_return('MSG_ERR_FAIL_SEND_CODE');   // 短信发送异常(ps: 1.长时间不用被禁用；2：IP变化，联系大汉三通服务商修改IP)
//        }

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

            // 增加用户操作log
            $action_id = 2105;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id = 0, $action_id, $params);

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
            $token = Token::model()->insertUserToken($user_id);

            // 修改验证码使用状态
            Code::model()->updateCode($mobile, $code, 1);

            // 增加用户操作log
            $action_id = 2106;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id, $action_id, $params);

            // 增加用户登录log
            Log::model()->user_log($user_id);

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
                // 更新登录时间
                User::model()->updateLastLoginTime($user_id);
                $data['userId'] = $user_id;
                $data['token'] = $token;

                // 增加用户操作log
                $action_id = 2101;
                $params = '';
                foreach ($_REQUEST as $key => $value)  {
                    $params .= $key . '=' . $value . '&';
                }
                $params = substr($params, 0, -1);
                Log::model()->action_log($user_id, $action_id, $params);

                // 增加用户登录log
                Log::model()->user_log($user_id);

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
                // 更新登录时间
                User::model()->updateLastLoginTime($user_id);
                $data['userId']             = $user_id;
                $data['token']              = $token;

                // 增加用户操作log
                $action_id = 2102;
                $params = '';
                foreach ($_REQUEST as $key => $value)  {
                    $params .= $key . '=' . $value . '&';
                }
                $params = substr($params, 0, -1);
                Log::model()->action_log($user_id, $action_id, $params);

                // 增加用户登录log
                Log::model()->user_log($user_id);

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

            // 更新登录时间
            User::model()->updateLastLoginTime($user_id);

            // 增加用户操作log
            $action_id = 2107;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id, $action_id, $params);

            // 增加用户登录log
            Log::model()->user_log($user_id);

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
                // 更新登录时间
                User::model()->updateLastLoginTime($user_id);

                // 增加用户操作log
                $action_id = 2108;
                $params = '';
                foreach ($_REQUEST as $key => $value)  {
                    $params .= $key . '=' . $value . '&';
                }
                $params = substr($params, 0, -1);
                Log::model()->action_log($user_id, $action_id, $params);

                // 增加用户登录log
                Log::model()->user_log($user_id);

                $this->_return('MSG_SUCCESS');
            }
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 用户绑定学员信息
     */
    public function actionBindMember()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
        || !isset($_REQUEST['salt']) || !isset($_REQUEST['mobile'])
        || !isset($_REQUEST['code']) || empty($_REQUEST['userId'])
        || empty($_REQUEST['token']) || empty($_REQUEST['salt'])
        || empty($_REQUEST['mobile']) || empty($_REQUEST['code'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $salt = trim(Yii::app()->request->getParam('salt', null));
        $mobile = trim(Yii::app()->request->getParam('mobile', null));
        $code = trim(Yii::app()->request->getParam('code', null));

        // 用户ID格式错误
        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_USER');
        }

        // 用户不存在，返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        // 验证token
        if (!Token::model()->verifyToken($user_id, $token)) {
            $this->_return('MSG_ERR_TOKEN');
        }

        // 验证码是否过期
        if (!Code::model()->verifyCode($mobile, $code, 3)) {
            $this->_return('MSG_ERR_CODE');
        }

        // 口令/手机号码 对应的用户是否存在
        $memberId = User::model()->verifySaltMobile($salt, $mobile);
        if (!$memberId) {
            $this->_return('MSG_ERR_SALT_MOBILE');
        }

        // 验证要添加的memberId是否和userId有绑定关系存在
        $existMemberId = User::model()->existUserIdMemberId($user_id, $memberId);
        if ($existMemberId) {
            $this->_return('MSG_ERR_INVALID_MEMBER');
        }

        // 验证绑定的学员ID数量,目前最多4个
        $countMemberId = User::model()->bindMemberNum($user_id);
        if ($countMemberId >= 4) {
            $this->_return('MSG_ERR_OVER_MEMBER');
        }

        // 创建事务
        $user_transaction = Yii::app()->cnhutong->beginTransaction();
        try {

            $nowTime = date("Y-m-d H:i:s");
            // 建立绑定关系
            User::model()->insertUserIdMemberId($user_id, $memberId, 1, $nowTime);

            // 修改验证码使用状态
            Code::model()->updateCode($mobile, $code, 3);

            // 增加用户操作log
            $action_id = 2109;
            $params = '';
            foreach ($_REQUEST as $key => $value)  {
                $params .= $key . '=' . $value . '&';
            }
            $params = substr($params, 0, -1);
            Log::model()->action_log($user_id, $action_id, $params);

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
     * 获得个人信息: 基本信息/绑定信息
     */
    public function actionGetUserInfo()
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

        $data = array();
        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {

            $userInfo = User::model()->getUserInfo($user_id);
            // 用户基本信息
            $data['nickName'] = $userInfo['username'];
            $data['points'] = $userInfo['score'];
            // 用户绑定信息
            $data['members'] = User::model()->getMembers($user_id);

            // 增加用户操作log
            $action_id = 2103;
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
     * 用户提交留言信息接口
     */
    public function actionPostMessage()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['teacherId']) || !isset($_REQUEST['content'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $teacherId = trim(Yii::app()->request->getParam('teacherId'));
        $content = trim(Yii::app()->request->getParam('content', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($teacherId) || $teacherId < 0 || empty($teacherId)) {
            $this->_return('MSG_ERR_FAIL_STUDENT');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 添加留言操作
            User::model()->postMessage($user_id, $teacherId, $content);
            $this->_return('MSG_SUCCESS');
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 用户查看留言列表接口
     */
    public function actionMyMessageList()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
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
            // 用户查看留言信息列表
            $data = User::model()->myMessageList($user_id);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 用户查看留言详情接口
     */
    public function actionMyMessageDetail()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['teacherId']) || !isset($_REQUEST['messageId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $teacherId = trim(Yii::app()->request->getParam('teacherId', null));
        $messageId = trim(Yii::app()->request->getParam('messageId', null));

        if (!ctype_digit($user_id)) {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        // 用户不存在,返回错误
        if ($user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($teacherId) || $teacherId < 0 || empty($teacherId)) {
            $this->_return('MSG_ERR_FAIL_STUDENT');
        }

        if (!ctype_digit($messageId) || $messageId < 0) {
            $this->_return('MSG_ERR_FAIL_MESSAGE');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 用户查看留言详情
            $data = User::model()->myMessageDetail($user_id, $teacherId, $messageId);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }


    /**
     *  用户投诉/举手
     */
    public function actionMyComplaint()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['departmentId']) || !isset($_REQUEST['name'])
            || !isset($_REQUEST['reason'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $departmentId = trim(Yii::app()->request->getParam('departmentId', null));
        $name = trim(Yii::app()->request->getParam('name', null));
        $reason = trim(Yii::app()->request->getParam('reason', null));

        // 用户名不存在,返回错误
        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($departmentId) || $departmentId < 1) {
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
            // 用户投诉/举手信息
            User::model()->myComplaint($user_id, $departmentId, $name, $reason, 2);
            $this->_return('MSG_SUCCESS');
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     *  消息状态接口
     */
    public function actionGetNoticeFlag()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 消息状态接口

            $data = User::model()->getNoticeFlag($user_id);

            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     *  用户意见反馈
     */
    public function actionPostFeedback()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['reason'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $reason = trim(Yii::app()->request->getParam('reason', null));

        // 用户名不存在,返回错误
        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (empty($reason)) {
            $this->_return('MSG_ERR_FAIL_REASON');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 用户投诉/举手信息
            $data = User::model()->postFeedBack($user_id, $reason, 2);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     *  用户意见反馈列表
     */
    public function actionFeedbackList()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['page'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $token = trim(Yii::app()->request->getParam('token', null));
        $page = trim(Yii::app()->request->getParam('page', null));

        // 用户名不存在,返回错误
        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($page) || $page < 0) {
            $this->_return('MSG_ERR_FAIL_PAGE');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {
            // 意见反馈列表
            $data = User::model()->feedBackList($user_id, $page);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}

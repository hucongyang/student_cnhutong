<?php
/*********************************************************
 * 错误码列表
 * 
 * @author  Lujia
 * @version 1.0 by Lujia @ 2013.12.23 创建错误列表
 ***********************************************************/
 
$_error_code = array(
		// 基本错误码
		'MSG_SUCCESS' 				        => array('10000', '成功'),
		'MSG_ERR_LESS_PARAM' 		        => array('10001', '请求缺少必要的参数'),
		'MSG_ERR_FAIL_PARAM' 		        => array('10002', '请求参数错误'),


	    // 用户相关错误码
        'MSG_ERR_NO_USER'                   => array('20001', '用户不存在'),
        'MSG_ERR_FAIL_MOBILE'				=> array('20002', '非法的手机号码'),
        'MSG_ERR_FAIL_CODE_TYPE'			=> array('20003', '非法的验证码类型'),
        'MSG_ERR_INVALID_MOBILE'			=> array('20004', '该手机号码已被注册'),
        'MSG_ERR_UN_REGISTER_MOBILE'		=> array('20005', '该手机号码未注册,请先注册'),
        'MSG_ERR_CODE'						=> array('20006', 'CODE验证错误'),
        'MSG_ERR_SET_SAME_PASSWORD'			=> array('20007', '用户名不能与密码相同'),
        'MSG_ERR_PASSWORD_WRONG'			=> array('20008', '您输入的密码错误'),
        'MSG_ERR_FAIL_USER'					=> array('20009', '用户ID格式错误'),
        'MSG_ERR_TOKEN'						=> array('20010', 'token错误'),
        'MSG_ERR_SALT_MOBILE'				=> array('20011', '口令对应的手机号码错误'),
        'MSG_ERR_INVALID_MEMBER'			=> array('20012', '口令对应用户memberId已绑定'),
        'MSG_ERR_OVER_MEMBER'				=> array('20013', '该用户已经绑定4个学员ID'),
        'MSG_ERR_FAIL_DATE_FORMAT'			=> array('20014', '日期格式错误'),
        'MSG_ERR_MEMBERS'					=> array('20015', '学员ID格式错误'),
        'MSG_ERR_FAIL_MEMBER'				=> array('20016', '存在非法的memberId'),
        'MSG_ERR_OVER_MEMBER_NUMBER'		=> array('20017', '超过规定数量memberId'),
        'MSG_ERR_FAIL_DATE_LESS'			=> array('20018', '缺少必要的日期内容'),


		// 其它
		'MSG_ERR_FAIL_SQL'					=> array('88888', 'SQL执行错误'),
		'MSG_ERR_UNKOWN'			=> array('99999', '系统繁忙，请稍后再试')
);

// return $ErrorCode;

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
        'MSG_ERR_PASSWORD_ERR'              => array('20002', '系统用户，不能登录'),
        'MSG_ERR_PASSWORD_WRONG'            => array('20003', '您输入的密码错误'),
        'MSG_ERR_TOKEN'                     => array('20004', 'TOKEN验证错误'),
        'MSG_ERR_DEPARTMENT'                => array('20005', '校区不存在'),
		'MSG_ERR_FAIL_LESSONSTUDENTIDS'		=> array('20006', '课时参数错误'),
		'MSG_ERR_FAIL_LESSON_FORMAT'		=> array('20007', '课时格式错误'),
		'MSG_ERR_FAIL_LESSONDETAILS'		=> array('20008', '课时详情参数错误'),
		'MSG_ERR_FAIL_DATE_FORMAT'			=> array('20009', '日期格式错误'),
		'MSG_ERR_FAIL_DATE_LESS'			=> array('20010', '缺少必要的日期内容'),
		'MSG_ERR_FAIL_SUBJECT'				=> array('20011', '课程参数错误'),
		'MSG_ERR_FAIL_DEPARTMENT'			=> array('20012', '校区参数错误'),
		'MSG_ERR_FAIL_STUDENT'				=> array('20013', '学员参数错误'),
		'MSG_ERR_FAIL_MESSAGE'				=> array('20014', '留言参数错误'),
		'MSG_ERR_FAIL_NAME'					=> array('20015', '名称格式错误'),
		'MSG_ERR_FAIL_REASON'				=> array('20016', '投诉格式错误'),
		'MSG_ERR_FAIL_PAGE'					=> array('20017', '分页参数错误'),
		'MSG_NO_STUDENT'					=> array('20018', '此学员不存在'),
		'MSG_OVER_TIME'						=> array('20019', '不可签到,存在签到课时不在签到时间范围内'),


	// 其它
		'MSG_ERR_FAIL_SQL'					=> array('88888', 'SQL执行错误'),
		'MSG_ERR_UNKOWN'			=> array('99999', '系统繁忙，请稍后再试')
);

// return $ErrorCode;

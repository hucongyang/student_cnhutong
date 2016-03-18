<?php

/**
 * Class NoticeController 消息管理部分
 */
class NoticeController extends ApiPublicController
{
    /**
     *  获取消息信息
     */
    public function actionGetNotices()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['page']) || !isset($_REQUEST['type']) ) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId'));
        $token = trim(Yii::app()->request->getParam('token'));
        $page = trim(Yii::app()->request->getParam('page'));
        $type = trim(Yii::app()->request->getParam('type'));

        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($page) || $page < 0) {
            $this->_return('MSG_ERR_FAIL_PAGE');
        }

        if (!ctype_digit($type) || !in_array($type, array(1,2,3))) {
            $this->_return('MSG_ERR_FAIL_TYPE');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {

            $data = Notice::model()->getNotices($user_id, $page, $type);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }

    }

    /**
     *  提交消息信息
     */
    public function actionPostNoticeReturn()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['token'])
            || !isset($_REQUEST['noticeId']) || !isset($_REQUEST['status']) ) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId'));
        $token = trim(Yii::app()->request->getParam('token'));
        $noticeId = trim(Yii::app()->request->getParam('noticeId'));
        $status = trim(Yii::app()->request->getParam('status'));

        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($noticeId) || ($noticeId < 1) || !(Notice::model()->isExistNoticeId($noticeId, $user_id)) ) {
            $this->_return('MSG_ERR_FAIL_NOTICE');
        }

        if (!ctype_digit($status) || !in_array($status, array(1, 2, 3, 4))) {
            $this->_return('MSG_ERR_FAIL_NOTICE_STATUS');
        }

        // 验证token
        if (Token::model()->verifyToken($user_id, $token)) {

            $data = Notice::model()->postNoticeReturn($noticeId, $status);
            $this->_return('MSG_SUCCESS', $data);
        } else {
            $this->_return('MSG_ERR_TOKEN');
        }

    }
}
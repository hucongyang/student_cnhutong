<?php

/**
 * 消息模型
 */
class Notice extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'com_notice';
    }

    /**
     * 添加消息详情
     * @param $sendId
     * @param $acceptId
     * @param $label
     * @param $leaveId
     * @param $extraId
     * @param $type
     * @param $title
     * @param $content
     * @param $sendTime
     * @param $flag
     * @param $status
     * @return bool
     */
    public function insertNotice($sendId, $acceptId, $label, $leaveId, $extraId, $type, $title, $content, $sendTime, $flag, $status)
    {
        $nowTime = date('Y-m-d H:i:s');
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()->insert(self::tableName(),
                array(
                    'send_id'                   => $sendId,
                    'accept_id'                 => $acceptId,
                    'label'                     => $label,
                    'leave_id'                  => $leaveId,
                    'extra_id'                  => $extraId,
                    'type'                      => $type,
                    'create_time'               => $nowTime,
                    'title'                     => $title,
                    'content'                   => $content,
                    'send_time'                 => $sendTime,
                    'flag'                      => $flag,
                    'status'                    => $status,
                )
            );

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * 获取消息信息
     * @param $user_id
     * @param $page
     * @param $type
     * @return array|bool
     */
    public function getNotices($user_id, $page, $type)
    {
        $data = array();
        try {

            $page = $page * 5;
            $pageLimit = " limit $page, 5";

            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT id, title, create_time as time, flag, status
                    FROM " . self::tableName() . "
                    WHERE accept_id = " . $user_id ." and type = " . $type . "
                    " . $pageLimit ."
                    ";
            $result = $con_user->createCommand($sql)->queryAll();
            $data['notices'] = $result;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }

    /**
     * 获取信息详情
     * @param $noticeId
     * @return array|bool
     */
    public function getNoticeDetail($noticeId)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT id, title, content, create_time as time, flag, status
                    FROM " . self::tableName() . "
                    WHERE id = " . $noticeId . " ";
            $result = $con_user->createCommand($sql)->queryAll();
            $data['noticeDetail'] = $result;

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }


    /**
     * 提交消息信息
     * @param $noticeId
     * @param $status
     * @return array|bool
     */
    public function postNoticeReturn($noticeId, $status)
    {
        $data = array();
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()->update(self::tableName(),
                array(
                    'status'        => $status
                ),
                'id = :noticeId',
                array(
                    ':noticeId'     => $noticeId
                )
            );

        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $data;
    }
}
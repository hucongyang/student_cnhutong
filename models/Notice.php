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
                    'send_time'                 => $nowTime,
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
     * 教师获取消息信息
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
            $sql = "SELECT id, title, content, create_time as time, flag, status
                    FROM " . self::tableName() . "
                    WHERE accept_id = " . $user_id ." and type = " . $type . "
                    ORDER BY create_time desc
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
     * 学生获得消息信息
     * @param $user_id
     * @param $page
     * @param $type
     * @return array|bool
     */
    public function getStudentNotices($user_id, $page, $type)
    {
        $data = array();
        try {

            $page = $page * 5;
            $pageLimit = " limit $page, 5";

            $con_user = Yii::app()->cnhutong;
            $sql = "SELECT n.id as id, title, content, create_time as time, flag, n.status as status
                    FROM com_notice n
                    LEFT JOIN com_user_member cum ON n.accept_id = cum.member_id
                    WHERE user_id = " . $user_id ." and type = " . $type . "
                    ORDER BY create_time desc
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

    /**
     * 校验消息ID和用户Id是否存在联系
     * @param $noticeId
     * @param $user_id
     * @return bool
     */
    public function isExistNoticeId($noticeId, $user_id)
    {
        try {
            $con_user = Yii::app()->cnhutong;
            $result = $con_user->createCommand()
                ->select('id')
                ->from('com_notice')
                ->where('accept_id = :acceptId and id = :noticeId',
                    array(
                        ':acceptId' => $user_id,
                        ':noticeId' => $noticeId
                    )
                )
                ->limit('1')
                ->queryScalar();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }
}
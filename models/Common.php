<?php

class Common extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function updateVersion($platform, $app_id)
    {
        try {
            $com_common = Yii::app()->cnhutong;
            $table_name = 'com_channel';
            $result = $com_common->createCommand()
                ->select('new_version as newVersion, download as url, create_ts as updateTime, content as updateContent')
                ->from($table_name)
                ->where('platform = :platform AND app_id = :app_id',
                    array(
                        ':platform'       => $platform,
                        ':app_id'         => $app_id
                    )
                )->order('id')
                ->limit('1')
                ->queryRow();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return $result;
    }
}
<?php

/**
 * Class Code 验证码相关模型
 */
class Code extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 生成验证码
     * @param $num      int 位数
     * @param $start    int 开始值
     * @param $end      int 结束值
     * @return string   生成的数值验证码
     */
    public function produceCode($num, $start, $end)
    {
        $range = range($start, $end);
        $arr = array();
        for($i = 0; $i < $num; $i++) {
            $arr[] =array_rand($range);
        }
        return implode("", $arr);
    }

    /**
     * 记录手机号码验证码生成状态
     * @param $mobile
     * @param $code
     * @param $nowTime
     * @param $overTime
     * @param $status
     * @param $type
     * @return bool
     */
    public function insertCode($mobile, $code, $nowTime, $overTime, $status, $type)
    {
        try {
            $con_code = Yii::app()->cnhutong;
            $table_name = 'com_log_mobile_checkcode';
            $con_code->createCommand()->insert($table_name, array(
                'mobile'                => $mobile,
                'checknum'              => $code,
                'create_ts'             => $nowTime,
                'expire_ts'             => $overTime,
                'status'                => $status,
                'type'                  => $type
            ));
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
        return true;
    }

    /**
     * 验证发送的验证码
     * @param $mobile
     * @param $code
     * @param $codeType
     * @return bool
     */
    public function verifyCode($mobile, $code, $codeType)
    {
        try {
            $con_code = Yii::app()->cnhutong;
            $table_name = 'com_log_mobile_checkcode';
            $result = $con_code->createCommand()
                    ->select('checknum, expire_ts')
                    ->from($table_name)
                    ->where('mobile = :mobile AND checknum = :code AND type = :type AND status = 0',
                        array(
                            ':mobile'       => $mobile,
                            ':code'         => $code,
                            ':type'         => $codeType
                        )
                    )->queryRow();
        } catch (Exception $e) {
            error_log($e);
            return false;
        }

        if (is_array($result)) {
            if ( ($result['checknum'] == (string)$code) && strtotime($result['expire_ts']) >= time() ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 注册成功/修改密码/绑定帐号后,修改验证码的使用状态
     * @param $mobile
     * @param $code
     * @param $type
     * @return bool
     */
    public function updateCode($mobile, $code, $type)
    {
        try {
            $con_code = Yii::app()->cnhutong;
            $table_name = 'com_log_mobile_checkcode';
            $con_code->createCommand()->update($table_name,
                array(
                    'status'            => 1
                ),
                'mobile = :mobile AND checknum = :code AND type = :type',
                array(
                    ':mobile'           => $mobile,
                    ':code'             => $code,
                    ':type'             => $type
                )
            );
        } catch (Exception $e) {
            error_log($e);
            return false;
        }
    }
}
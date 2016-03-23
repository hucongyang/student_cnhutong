<?php

/**
 * Class CommonController 公共控制器
 */
class CommonController extends ApiPublicController
{
    /**
     * 版本升级
     * $GLOBALS['__PLATFORM']   系统平台: 1-Android; 2-iOS
     * $GLOBALS['__APP_ID']     应用编号: 10-学员端; 11-教师端
     */
    public function actionUpdateVersion()
    {
        $platform = intval(trim($GLOBALS['__PLATFORM']));
        $app_id = intval(trim($GLOBALS['__APP_ID']));

        $plat = array(1, 2);        // 平台数组: 1-Android; 2-iOS
        $app = array(10, 11);       // 应用编号数组: 10-学员端; 11-教师端

        if ( in_array($platform, $plat, true) && in_array($app_id, $app, true) ) {
            $result = Common::model()->updateVersion($platform, $app_id);
            if ($result === false) {
                $this->_return('MSG_ERR_UNKOWN');
            }

            $data = array();
            if (isset($result['newVersion'])) {
                if (strcmp($_REQUEST['app_version'], $result['newVersion']) != 0) {
                    $data = $result;
                }
            }

            if (empty($data)) {
                $this->_return('MSG_ERR_FAIL_UPDATE_VERSION');
            } else {

                // 增加用户操作log
                $action_id = 2401;
                $params = '';
                foreach ($_REQUEST as $key => $value)  {
                    $params .= $key . '=' . $value . '&';
                }
                $params = substr($params, 0, -1);
                Log::model()->action_log(0, $action_id, $params);


                $this->_return('MSG_SUCCESS', $data);
            }

        } else {
            $this->_return('MSG_ERR_FAIL_PLATFORM|APP_ID');
        }
    }

    /**
     *  获取校区列表
     */
    public function actionGetAllSchools()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['type'])
            || empty($_REQUEST['userId']) || empty($_REQUEST['type'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $type = trim(Yii::app()->request->getParam('type', null));

        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        $typeArr = array(1, 2, 3, 4);

        if (!ctype_digit($type) || !in_array($type, $typeArr)) {
            $this->_return('MSG_ERR_FAIL_TYPE');
        }

        $data = Common::model()->getAllSchools($type);

        $this->_return('MSG_SUCCESS', $data);

    }

    /**
     *  获取校区详情
     */
    public function actionGetSchoolInfo()
    {
        if (!isset($_REQUEST['userId']) || !isset($_REQUEST['departmentId'])
            || empty($_REQUEST['userId']) || empty($_REQUEST['departmentId'])) {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id = trim(Yii::app()->request->getParam('userId', null));
        $departmentId = trim(Yii::app()->request->getParam('departmentId', null));

        if (!ctype_digit($user_id) || $user_id < 1) {
            $this->_return('MSG_ERR_NO_USER');
        }

        if (!ctype_digit($departmentId) || $departmentId < 1) {
            $this->_return('MSG_ERR_FAIL_DEPARTMENT');
        }

        $data = Common::model()->getSchoolInfo($departmentId);

        $this->_return('MSG_SUCCESS', $data);

    }
}
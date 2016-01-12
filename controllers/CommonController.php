<?php

/**
 * Class CommonController 公共控制器
 */
class CommonController extends ApiPublicController
{
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
                $this->_return('MSG_SUCCESS', $data);
            }

        } else {
            $this->_return('MSG_ERR_FAIL_PLATFORM|APP_ID');
        }
    }
}
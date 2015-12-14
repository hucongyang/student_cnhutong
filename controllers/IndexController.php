<?php
/**
 * IndexController 默认控制器
 */
class IndexController extends ApiPublicController
{
    public function actionIndex()
    {
        echo "Test The YII Framework" . "\n";
        echo date("Y-m-d H:i:s", strtotime("now")) . "\n";
        echo "HELLO WORLD" . "\n";
        echo time() + strtotime('+ 30day') . "\n";
        echo date("H") . "\n";
        echo date("H-i-s");
        echo phpinfo();
    }

    public function actionJson()
    {
        $arr = array(
            'a' => 256996,
            'b' => 0,
            'c' => 429587,
            'd' => 2
        );
        var_dump($arr);
        $json = json_encode($arr);
        var_dump($json);

        $lesson = '{[{"lessonStudentId":"256996","step":"0"},{"lessonStudentId":"429587","step":"2"}]';
        var_dump($lesson);
        $lessonJson = json_decode($lesson);
        var_dump($lessonJson);
    }
}
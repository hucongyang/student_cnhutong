<?php
/**
 * IndexController 默认控制器
 */
class IndexController extends Controller
{
    public function actionIndex()
    {
        $this->renderPartial('index');
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
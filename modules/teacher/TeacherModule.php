<?php

class TeacherModule extends CWebModule
{
    public function init()
    {
        $this->defaultController = "index";

        $this->setImport(array(
            'teacher.models.*',
            'teacher.components.*',
            'teacher.controllers.*'
        ));
    }
}
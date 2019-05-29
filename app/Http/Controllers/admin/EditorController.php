<?php

namespace App\Http\Controllers\admin;

class EditorController extends AdminBaseController
{
    public function wangEditor()
    {
        return view('admin.editor.wangEditor');
    }
}
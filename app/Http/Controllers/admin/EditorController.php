<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class EditorController extends AdminBaseController
{
    public function wangEditor()
    {
        return view('admin.editor.wangEditor');
    }

    public function uploadPic(Request $request)
    {
        foreach ($request->all() as $one)
        {
            if ($one instanceof UploadedFile)
            {
                $tmp[]=$one;
            }
        }

        dd($tmp);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{

    /**
     * 网站首页
     */
    public function home()
    {
        return view('home.index');
    }
}

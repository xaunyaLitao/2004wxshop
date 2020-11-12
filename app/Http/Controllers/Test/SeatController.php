<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeatController extends Controller
{

    public function index()
    {
        return view('test.seat');
    }
}

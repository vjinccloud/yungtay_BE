<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * AFL 前台 - 關於我們 Controller
 */
class AflAboutController extends Controller
{
    /**
     * 使命與願景頁面
     */
    public function mission()
    {
        return view('frontend.afl.about.mission');
    }

    /**
     * 創辦人的話頁面
     */
    public function founder()
    {
        return view('frontend.afl.about.index');
    }
}

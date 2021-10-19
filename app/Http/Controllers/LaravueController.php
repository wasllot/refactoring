<?php
/**
 * File LaravelController.php
 *
 * @author Reinaldo Tineo <rei.vzl@gmail.com>
 * @package Laravue
 * @version 1.0
 */

namespace App\Http\Controllers;

/**
 * Class LaravueController
 *
 * @package App\Http\Controllers
 */
class LaravueController extends Controller
{
    /**
     * Entry point for Laravue Dashboard
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('laravue');
    }
}

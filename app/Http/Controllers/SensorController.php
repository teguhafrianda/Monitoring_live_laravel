<?php // app/Http/Controllers/SensorController.php

namespace App\Http\Controllers;

class SensorController extends Controller
{
    public function index()
    {
        // This controller now only loads the main view.
        // All data will be fetched asynchronously by JavaScript.
        return view('data-sensor');
    }
}
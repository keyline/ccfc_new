<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class HdfcGatewayTestingController extends Controller
{
    public function index()
    {
        return view('hdfc-testing.hdfc-demo');
    }
}
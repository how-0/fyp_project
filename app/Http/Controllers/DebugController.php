<?php

namespace App\Http\Controllers;

use Gemini\Laravel\Facades\Gemini;

class DebugController extends Controller
{
    public function index()
    {
        // $result = Gemini::generativeModel(model: 'gemini-2.5-flash')->generateContent('What is this a good place to visit in penang?');
        // dd($result->text());
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Review Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling reviews
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }
    
    /**
     * redirects to review.
     *
     * @return void
     */
    public function reviews(){
    	return view('reviews/review');
    }
}

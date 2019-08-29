<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator; use Input; //Input data  validation and getting data
use Auth; use Session; //Security
use Illuminate\Support\Facades\Redirect;

class LoginController extends Controller {

//    use CacheFilter; //Changing the cacahe from the view caching to data caching.

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        
    }
    
    /**
     * Login a user using auth and get his/her new session_id.
     * 
     * @param Request $request
     * @return Illuminate\View\View
     */
    public function login(Request $request) { 
        
        if ($request->isMethod('post')) {
           
            
            if( isset(Auth::user()->username) && (Auth::user()->username != "") ){
                $request->session()->flash('error', 'You are already logged-in.');
                return redirect()->back();
            } 
            
            // Create validation rules
            $rules = [
                'email' => 'required|email',
                'password' => 'required'
            ];
            
            // Validate against the inputs
            $form_validator = Validator::make($request->all(), $rules);

            // If the validator fails, 
            if ($form_validator->fails()) {
                return redirect()->back()->withErrors($form_validator->errors())->withInput(); // Redirect back to the form with the errors from the validator
            } else {
                $parameters = ['email' => $request->input('email'), 'password' => $request->input('password')];
                if (Auth::attempt($parameters)) {
                    
                  
                    
                    //Get the complete listing of adresses with timezone.
                    $parameters = ['username' => Auth::user()->username, 'session_id' => Auth::user()->session_id];
                    $getCountriesList = $this->incidentRepository->getCountriesList($parameters);
                    if ( (isset($getCountriesList['status'])) && ($getCountriesList['status'] == 'success') && isset($getCountriesList['countries']) && (is_array($getCountriesList['countries']) && (!empty($getCountriesList['countries'])))) {
                       echo 1;exit;
                } else {
                     
                    $errorMsg = trim($request->session()->get('login_error'));
                    $request->session()->flash('error', $errorMsg);
                    // if($errorMsg == 'Account has been locked out because of multiple failed login attempts.'){
                    //     return redirect('password/reset');
                        
                       
                      
                    // }else if($errorMsg == 'Password has expired.'){
                    //     //return redirect('password/reset');
                        
                    //    $username    = $request->input('username');
                    //    $pass        = $request->input('password');
                       
                       
                    //    $request->session()->put('username_cp', $username);
                    //    $request->session()->put('password_cp', $pass);
                    //    return redirect('password/change');
                      
                    // }
                }
            }
        } else {
             
            if( isset(Auth::user()->username) && (Auth::user()->username != "") ){
                return Redirect::route('cp-dashboard-home');
            }
            
        }
        

        
        return view('fv-portal.login');
    }


}

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use Socialite;
use Auth;
use Exception;

class SocialAuthGoogleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Social Auth Google Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling  Google Oauth Login.
    |
    */
    
    
    /**
     * redirect to google     
     * @return array to call back
    */ 
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * check geoking database with the user array returned by google    
     * @return  
    */ 
    public function callback()
    {
        try {
        
            $googleUser = Socialite::driver('google')->stateless()->user();
            $existUser = User::where('email',$googleUser->email)->first();

            if($existUser) {
                Auth::loginUsingId($existUser->id);
                $user = User::where('id', Auth::user()->id)->first();
                if(strtotime(date('Y-m-d')) > strtotime($user->expired_date)){
                    Auth::logout();
                    return redirect()->back()->withError('Your account has been expired!');
                }
                User::where('id', Auth::user()->id)->update(['last_login_date' => date('Y-m-d H:i:s')]);
                if($user->logged_in == 0){
                    //User::where('id', Auth::user()->id)->update(['logged_in' => 1]);
                    return redirect('get-started');
                }

                return redirect()->to('/dashboard');
            }
            else {
                $password = rand(10000000, 99999999);
                $expired_date = date('Y-m-d',strtotime('+30 days',strtotime(date('m/d/Y'))));
                $name = explode(' ', $googleUser->name);
                $user = new User;
                $user->name = $name[0];
                unset($name[0]);
                $user->last_name = trim(implode(" ",$name));
                $user->email_verified_at = date('Y-m-d H:i:s');
                $user->oauth_provider = 'google';
                $user->oauth_id = $googleUser->id;
                $user->oauth_token = $googleUser->token;
                $user->email = $googleUser->email;               
                $user->password = Hash::make($password);
                $user->activation_date = date('Y-m-d');
                $user->expired_date = $expired_date;
                $user->last_login_date = date('Y-m-d H:i:s');
                //$user->logged_in = 1;
                $user->save();

                Auth::loginUsingId($user->id);
                return redirect()->to('/get-started');
            }
            
        } 
        catch (Exception $e) {
            return redirect('/login');           
        }
    }
}
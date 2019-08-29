<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Session;
use App\User;
use App\Models\user_competitors;
use App\Models\user_team;
use App\Models\industries;
use App\Models\industry_type;

class HomeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Home Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling user basic info like 
    | getstarted, tell about yourself, tell about your comapny etc
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
     * redirects to check_your_email.       
     * @return void
    */ 
    public function check_your_email(){
        return view('home/check_your_email');
    }

    /**
     * redirects to get_started.          
     * @return user
    */ 
    public function get_started(){
        $user = auth()->user();
        return view('home/get_started', ['user' => $user]);
    }

    /**
     * redirects to tel_about.          
     * @return user
    */ 
    public function tel_about(){
        $user = auth()->user();
        return view('home/tel_about')->with(['user' => $user]);
    }

    /**
     * save basic info and redirects to tel-about-company.
     * @param Request $request           
     * @return void
    */ 
    public function save_basic_info(Request $request){
        $user = User::where('id', $request->user_id)->first();
        $profile_picture = $user->profile_picture;
        if(array_key_exists('profile_picture', $request->all())){
            if($profile_picture != '' && file_exists(public_path().'/uploads/profile_picture/'.$profile_picture)){
                unlink(public_path().'/uploads/profile_picture/'.$profile_picture);
            }
            $image = $request->file('profile_picture');
            $profile_picture = $request->user_id.'-'.time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/profile_picture');
            $image->move($destinationPath, $profile_picture);
        }

        $notify = 0;
        if(array_key_exists('notify_me', $request->all())){
            $notify = $request->notify_me;
        }
        User::where('id', $request->user_id)->update(['name' => $request->name, 'last_name' => $request->last_name, 'phone_number' => $request->phone_number, 'notify_me' => $notify, 'profile_picture' => $profile_picture]);

        return redirect('tel-about-company');
    }

    /**
     * redirects to tel-about-company.         
     * @return $user and $industries
    */ 
    public function tel_about_company(){
        $user = auth()->user();
        $industries = industries::get();
        return view('home/tel_about_company')->with(['user' => $user, 'industries' => $industries]);
    }
    
    
    /**
     * redirects to tel_about_competition.          
     * @return $user, $user_competitors
    */ 
    public function tel_about_competition(){
        $user = auth()->user();
        $user_competitors = user_competitors::where('user_id', $user->id)->get();
        return view('home/tel_about_competition')->with(['user' => $user, 'user_competitors' => $user_competitors]);
    }
    
    
    /**
     * redirects to tel_about_team.          
     * @return $user, $user_teams
    */ 

    public function tel_about_team(){
        $user = auth()->user();
        $userTeams = user_team::where('user_id', $user->id)->get()->toArray();
        $user_teams = array();
        foreach($userTeams as $userTeam){
            $user_teams[$userTeam['designation_no']] = $userTeam;
        }
        // echo "<pre>";print_r($user_teams);exit;
        return view('home/tel_about_team')->with(['user' => $user, 'user_teams' => $user_teams]);
    }

    /**
     * save business location type and redirects to tel-about. 
     * @param Request $request          
     * @return $user, $user_teams
    */ 
    public function save_business_location_type(Request $request){
        $user = auth()->user();
        User::where('id', $user->id)->update(['business_location_type' => $request->r1]);

        return redirect('tel-about');
    }
    
    /**
     * save save_company_info and redirects to tel-about-competition. 
     * @param Request $request          
     * @return void
    */ 

    public function save_company_info(Request $request){
        User::where('id', $request->user_id)->update(['company_name' => $request->company_name, 'industry_type' => $request->industry_type, 'business_type' => $request->business_type, 'verified_business_location' => $request->verified_business_location, 'business_locations' => $request->business_locations, 'logged_in' => 1]);

        return redirect('tel-about-competition');
    }

    
    /**
     * save competiters and redirects to tel-about-team. 
     * @param Request $request          
     * @return void
    */ 
    public function save_competiters(Request $request){
        if(array_key_exists('competiter_name', $request->all())){
            user_competitors::where('user_id', $request->user_id)->delete();
            foreach($request->competiter_name as $competiter_name){
                user_competitors::insert(['user_id' => $request->user_id, 'competitor' => $competiter_name, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        
        return redirect('tel-about-team');
    }

    /**
     * save team and redirects to dashboard. 
     * @param Request $request          
     * @return void
    */ 
    public function save_team(Request $request){
        $input = $request->all();
        // user_team::where('user_id', $input['user_id'])->delete();
        $i = 0;
        foreach($input['designation_no'] as $designation_no){

            $user_team = user_team::where('user_id', $input['user_id'])->where('designation_no', $designation_no)->first();
            user_team::where('user_id', $input['user_id'])->where('designation_no', $designation_no)->delete();
            if($user_team == ''){
                $profile_picture = '';
            }else{
                $profile_picture = $user_team->profile_picture;
            }
            if(array_key_exists('profile_picture_'.$designation_no, $input)){
                    $image = $request->file('profile_picture_'.$designation_no);
                    $profile_picture = $input['user_id'].'-'.$i.'-'.time().'.'.$image->getClientOriginalExtension();
                    $destinationPath = public_path('/uploads/profile_picture');
                    $image->move($destinationPath, $profile_picture);
                
            }


            $instant_alert = 0;
            if(array_key_exists('instant_alerts_'.$input['designation_no'][$i], $input)){
                $instant_alert = $input['instant_alerts_'.$input['designation_no'][$i]];
            }

            $exec_summary = 0;
            if(array_key_exists('exec_summary_'.$input['designation_no'][$i], $input)){
                $exec_summary = $input['exec_summary_'.$input['designation_no'][$i]];
            }

            user_team::insert(['user_id' => $input['user_id'],
                                'name' => $input['name'][$i],
                                'email' => $input['email'][$i],
                                'profile_picture' => $profile_picture,
                                'designation' => $input['designation_name'][$i],
                                'designation_no' => $input['designation_no'][$i],
                                'instant_alert' => $instant_alert,
                                'exec_summary' => $exec_summary,
                                'frequency' => $input['frequency'][$i],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
            $i++;
        }

        return redirect('/dashboard');

        
    }

    /**
     * get businesstype. 
     * @param Request $industry          
     * @return $option
    */ 
    public function getBusinessType($industry = null){
        if($industry == ''){
            return '<option value="">-- Select --</option>';
        }

        $user = auth()->user();

        $industry_types = industry_type::where('industry_id', $industry)->get();
        $option = '<option value="">&nbsp;Select</option>';
        foreach ($industry_types as $industry_type) {
            if($user->business_type == $industry_type->id){
                $option = $option.'<option value="'.$industry_type->id.'" selected>&nbsp;'.$industry_type->name_en.'</option>';
            }else{
                $option = $option.'<option value="'.$industry_type->id.'">&nbsp;'.$industry_type->name_en.'</option>';
            }
        }

        return $option;
    }
}

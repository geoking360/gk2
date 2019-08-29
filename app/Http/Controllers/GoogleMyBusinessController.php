<?php

namespace App\Http\Controllers;
use App\Classes\gmb\lib\Google_my_business;
use Illuminate\Http\Request;
use App\User;
use Socialite;
use Auth;
use Exception;

class GoogleMyBusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Google My Business Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling all API Requests send to GMB.
    |
    */
    private $Google_my_business; 
    
    /**
    * Create a new controller instance.
    *
    * @return void
    */
    public function __construct()
    {        
        $this->middleware(['auth', 'verified']);
        $param = array(
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_GMB'),
            'scope' => env('SCOPES')
        );       
        $this->Google_my_business = new Google_my_business($param);
    }
    
    /**
     * redirect to google.
     * 
     * @param Request $param     * 
     * @return code to callback
    */    
    public function redirectgmb()
    {
       $url = $this->Google_my_business->gmb_login();       
       return redirect()->away($url);
    }
    
    /**
     * get the code from Google and generate access_token.
     *       
     * @return code to callback
    */ 
    public function callbackgmb()
    {       
        $code = filter_input(INPUT_GET, 'code');       
        if (!isset($code) || empty($code))
        {
            return redirect('/login');
        }
        $access_token = $this->Google_my_business->get_access_token($code);
        
        if(isset($access_token['error']))
        {
            //echo "<p style='color: red; font-weight: bold;'> Errors: " . $access_token['error'] . " => " . $access_token['error_description'] . "</p>";
            //echo "<p><a href='login.php'>Back to Login page</a></p>";
            return redirect('/login');
        }
        session(['refresh_token' => $access_token['refresh_token']]);          
        return redirect('/gmblocations');        
    }
    
    /**
     * refresh token
     * @param Request session(refresh_token)     
     * @return access_token
    */ 
    public function chktoken(){       
         if(trim(session('refresh_token')!==NULL) && trim(session('refresh_token'))!==''){
             $refresh_token = trim(session('refresh_token'));
         }        
         if (!isset($refresh_token) || empty($refresh_token))
         {            
             return redirect('/home');
         }
         return $access_token = $this->Google_my_business->get_exchange_token($refresh_token);        
    }
    
    
    /**
     * Get Categories 
     * @param Request access_token     
     * @return categories
    */ 
    public function gmbcategories()
    {
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }       
        $categories = $this->Google_my_business->get_categories($access_token['access_token']);
        
    }
    
    /**
     * Get List of All Accounts  
     * @param Request access_token     
     * @return All Accounts 
    */ 
    
     public function get_allaccount()
    {
        $account_details = array();
        $access_token = $this->chktoken();
       
        if(is_object($access_token) ){ return redirect('/dashboard'); }   
        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
      

        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);           
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            $account_details = $this->Google_my_business->get_account_details(session('gmb_account_name'), $access_token['access_token']);
   
        }
        return $account_details;        
    }
    
    
    /**
     * Get Accounts Details  
     * @param Request access_token     
     * @return Accounts Details 
    */ 
    public function gmbaccount()
    {
        
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   
        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
      

        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);           
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);   

            $account_details = $this->Google_my_business->get_account_details(session('gmb_account_name'), $access_token['access_token']);
            $get_notifications = $this->Google_my_business->get_notifications(session('gmb_account_name'), $access_token['access_token']);

        }
        return redirect('/home');        
    }
    
    
    
    /**
     * Get Locations   
     * @param Request $request     
     * @return Locations 
    */ 
    public function gmblocations(Request $request, $accountId='')
    {
        
        
        //$data = $request->all();
        $data_array = $locations = $allLocations = $all_arr = $arrSum = array(); 
        $varifiedSum = $totalSum  = $pendingSum = 0; 
        $allaccount     = $this->get_allaccount(); 
        
        $access_token   = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }
        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);       
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {         
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);            
            session(['gmb_user_name'    => $accounts['accounts'][0]['accountName']]);             
            if(isset($accountId) && $accountId!=''){
                // Get Locations By Account Id
                $data_array = $this->Google_my_business->get_locations('accounts/'.$accountId, $access_token['access_token']);
                if(empty($data_array)){
                  $locations['error'] = 'No Locations Found';  
                }else{
                    $locations['data']= $data_array;                  
                    $arr = $this->calculation_location_heads($accountId, $data_array);
                    $locations['calculations'] = $arr;                   
                }   
            }else{
                //$locations['error'] = 'Please select an account to get locations ';    
                // Get Locations of All the accounts
                $x= 0;
                foreach ($accounts['accounts'] as $account_arr){                
                    $accountId1 = substr($account_arr['name'], strrpos($account_arr['name'], '/') + 1);
                    $data_array = $this->Google_my_business->get_locations('accounts/'.$accountId1, $access_token['access_token']);
                    $arr = $this->calculation_location_heads($accountId1, $data_array);
                    if(!empty($arr)){
                        $totalSum       = $totalSum    + $arr['total'];
                        $varifiedSum    = $varifiedSum + $arr['varified'];              
                        $pendingSum     = $pendingSum  + $arr['pending'];
                    }
                    if(!empty($data_array)){                   
                        foreach($data_array as $locations_arr){                     
                            foreach($locations_arr as $loc){                                 
                                $all_arr['locations'][$x] = $loc;
                            $x++;
                            }                         
                        }                     
                        $allLocations['data'] = $all_arr;
                    }                    
                }
                $arrSum['total']     = $totalSum;
                $arrSum['varified']  = $varifiedSum;
                $arrSum['pending']   = $pendingSum;
                $allLocations['calculations']   = $arrSum;
                $locations = $allLocations;                
            }             
        }
        return view('locations/index', compact('locations', 'allaccount' ,'accountId'));
    }
    
    
    /**
     * calculation location heads  
     * @param Request $accountId, array $locations     
     * @return  total, varified and pending count
    */ 
    public function calculation_location_heads($accountId, $locations){       
        $varified = $total  = $pending = 0;
        $arr = array();
         
        if(isset($locations['locations'])){
            $total = count($locations['locations']);
            foreach($locations['locations'] as $loca_arr){               
                if(isset($loca_arr['locationState']['isPublished'])){
                    $varified = $varified + 1;
                }          
            }
            $pending        = $total - $varified ;  
            $arr['total']   = $total;
            $arr['varified']= $varified;
            $arr['pending'] = $pending;
        } 
        
        return $arr;
    }
    
    
    /**
     * calculation response rate  
     * @param Request $accountId, $locationId     
     * @return  array 
    */ 
    public static function calculation_response_rate($accountId, $locationId){
        $arr = array();
        
        /* set parameters */
        $param = array(
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_GMB'),
            'scope' => env('SCOPES')
        );       
        $GMB = new Google_my_business($param);
        
        if(trim(session('refresh_token')!==NULL) && trim(session('refresh_token'))!==''){
            $refresh_token = trim(session('refresh_token'));
        }        
        if (!isset($refresh_token) || empty($refresh_token))
        {            
            return redirect('/home');
        }
        $access_token = $GMB->get_exchange_token($refresh_token); 
        
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $GMB->get_accounts($access_token['access_token']);
        
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);    
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            if($accountId!='' && $locationId!=''){
                $reviews = $GMB->get_reviews('accounts/'.$accountId.'/locations/'.$locationId.'/reviews', $access_token['access_token']);
                
                if(!empty($reviews)){
                    $reviews_replied= 0;
                   foreach ($reviews as $key => $value) {                      
                        if($key=='reviews'){
                            foreach($value as $rev){
                                if(isset($rev['reviewReply']) && !empty($rev['reviewReply'])){
                                    $reviews_replied = $reviews_replied + 1;
                                }
                            }
                        }elseif($key=='averageRating'){
                            $rating = $value;
                        }else{
                            $total_reviews = $value;
                        } 
                    }
                    $pending_reviews =  $total_reviews-$reviews_replied;                    
                    $arr['total_reviews']   =   $total_reviews;
                    $arr['pending_reviews'] =   $pending_reviews;
                    $arr['reviews_replied'] =   $reviews_replied;
                    $arr['response_rate'] =   ( $reviews_replied / $total_reviews )*100;
                }
                
            }  
        }
        
        return $arr;
        
    }
    
    
    /**
     * get reviews for a location  
     * @param Request $accountId, $locationId     
     * @return  array 
    */ 
    public function gmbreviewscalculation($accountId, $locationId)
    {
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
        
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);    
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            if($accountId!='' && $locationId!=''){
                $reviews = $this->Google_my_business->get_reviews('accounts/'.$accountId.'/locations/'.$locationId.'/reviews', $access_token['access_token']);
                return $reviews;
                
            }  
        }
        
    }
    
    
    /**
     * delete a location  
     * @param Request $request     
     * @return  response 
    */ 
    public function gmblocationsdelete(Request $request)
    {
       $accountid =  $request->accountid; 
       $locationid = $request->locationid; 
       
        
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
  
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);            
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            $response = $this->Google_my_business->delete_media('accounts/'.$accountid.'/locations/'.$locationid, $access_token['access_token']);
            if(isset($response['error']) ){
                return response()->json(['error'=>$response['error']['message']]);
            }else{
                return response()->json(['success'=>'Deleted Successfully']);
            }
            
        }
        
        return view("/home", compact('locations'));
    }
    
    
    /**
     * Add a location  
     * @param Request $postBody     
     * @return  response 
    */ 
    public function gmbaddlocation()
    { 
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }  
        
        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);

        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);            
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            $postBody = array (
                    'locationName' => 'forseesolution',
                    'primaryPhone' => '091006 32822',
                    'primaryCategory' => 
                    array (
                      'displayName' => 'Software company',
                      'categoryId' => 'gcid:software_company',
                    ),
                    'websiteUrl' => 'https://4c360.net/ts/',
                    'serviceArea' => 
                    array (
                      'businessType' => 'CUSTOMER_AND_BUSINESS_LOCATION',
                      'places' => 
                      array (
                        'placeInfos' => 
                        array (
                          0 => 
                          array (
                            'name' => '500025, Hyderabad, Telangana',
                            'placeId' => 'ChIJdZODNYiZyzsRCq_7VoaZvis',
                          ),
                        ),
                      ),
                    ),
                    'locationKey' => 
                    array (
                      'placeId' => 'ChIJPx7HoMGXyzsRDwCDQESOC90',
                      'requestId' => 'df2af6da-9389-4c15-9fb5-cd1e85815862',
                    ),
                    'latlng' => 
                    array (
                      'latitude' => 17.414586799999998589782990165986120700836181640625,
                      'longitude' => 78.4382676999999972622390487231314182281494140625,
                    ),
                    'openInfo' => 
                    array (
                      'status' => 'OPEN',
                      'canReopen' => true,
                    ),
                    'locationState' => 
                    array (
                      'canUpdate' => true,
                      'canDelete' => true,
                      'isVerified' => true,
                      'isPublished' => true,
                    ),
                    'metadata' => 
                    array (
                      'mapsUrl' => 'https://maps.google.com/maps?cid=15927980930917138447',
                      'newReviewUrl' => 'https://search.google.com/local/writereview?placeid=ChIJPx7HoMGXyzsRDwCDQESOC90',
                    ),
                    'languageCode' => 'en',
                    'address' => 
                    array (
                      'regionCode' => 'IN',
                      'languageCode' => 'en',
                      'postalCode' => '500034',
                      'administrativeArea' => 'Telangana',
                      'locality' => 'Hyderabad',
                      'addressLines' => 
                      array (
                        0 => '8-2-277,3rd floor,Foresee 360 solutions P.vt L.td, kingston Height',
                        1 => 'Road No. 2Banjara Hill, Hyderabad, Adj to Birth Place Hospital,Telangana ',
                        2 => '500034',
                      ),
                    ),
                  );
            $response = $this->Google_my_business->add_locations('accounts/116775430050800689419/locations', $access_token['access_token'], $postBody);
            
            if(isset($response['error']['code'])){
                $error =  $response['error']['message'];                
            }else{
                 $error =  'Added'; 
            };
        }
        
        return view('home',compact('error'));  
        
    }
    
    
    /**
     * Update a location  
     * @param Request $postBody     
     * @return  response 
    */ 
    public function gmbupdatelocation()
    {
        
        
        
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   
        
        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);

        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);             
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            $postBody = array (
                'name' => 'accounts/116775430050800689419/locations/2093285886886357603',
                'locationName' => 'forseesolution',
                'primaryPhone' => '091006 32822',
                'primaryCategory' => 
                array (
                  'displayName' => 'IT Software company',
                  'categoryId' => 'gcid:software_company',
                ),
                'websiteUrl' => 'https://4c360.net/ts/',
                'serviceArea' => 
                array (
                  'businessType' => 'CUSTOMER_AND_BUSINESS_LOCATION',
                  'places' => 
                  array (
                    'placeInfos' => 
                    array (
                      0 => 
                      array (
                        'name' => '500025, Hyderabad, Telangana',
                        'placeId' => 'ChIJdZODNYiZyzsRCq_7VoaZvis',
                      ),
                    ),
                  ),
                ),
                'locationKey' => 
                array (
                  'placeId' => 'ChIJPx7HoMGXyzsRDwCDQESOC90',
                  'requestId' => 'df2af6da-9389-4c15-9fb5-cd1e85815862',
                ),
                'latlng' => 
                array (
                  'latitude' => 17.4145868,
                  'longitude' => 78.4382677,
                ),
                'openInfo' => 
                array (
                  'status' => 'OPEN',
                  'canReopen' => true,
                ),
                'locationState' => 
                array (
                  'canUpdate' => true,
                  'canDelete' => true,
                  'isVerified' => true,
                  'isPublished' => true,
                ),
                'metadata' => 
                array (
                  'mapsUrl' => 'https://maps.google.com/maps?cid=15927980930917138447',
                  'newReviewUrl' => 'https://search.google.com/local/writereview?placeid=ChIJPx7HoMGXyzsRDwCDQESOC90',
                ),
                'languageCode' => 'en',
                'address' => 
                array (
                  'regionCode' => 'IN',
                  'languageCode' => 'en',
                  'postalCode' => '500034',
                  'administrativeArea' => 'Telangana',
                  'locality' => 'Hyderabad',
                  'addressLines' => 
                  array (
                    0 => '8-2-277,3rd floor,Foresee 360 solutions P.vt L.td, kingston Height',
                    1 => 'Road No. 2Banjara Hill, Hyderabad, Adj to Birth Place Hospital,Telangana ',
                    2 => '500034',
                  ),
                ),
              );
            
            $fieldMask = array (
                    'mask' => 'user.displayName',
                  );
            $validateOnly = false;
            $response = $this->Google_my_business->update_media('accounts/116775430050800689419/locations/2093285886886357603', $access_token['access_token'], $fieldMask, $postBody, $validateOnly);
            
            if(isset($response['error']['code'])){
                $error =  $response['error']['message'];                
            }else{
                 $error =  'Added'; 
            };
            
        }
        
        return view('home',compact('error'));  
        
    }
    
    
    
    /**
     * Reply a review   
     * @param Request $postBody     
     * @return  response 
    */ 
    public function gmbupdatereviews()
    {
        
        
        
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);    
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            $postBody = array(
                "comment" => "Thank you Simdhari for posting your reviews."

            );

            $reviews = $this->Google_my_business->reply_review("accounts/116775430050800689419/locations/2093285886886357603/reviews/AIe9_BFMK6FB7k24ANEBV5CospTTwOolT1wteZidFsno__W-ULDzP8rNKruckyzfZBgiOWmH_G_VjAsB3B9FoUSXpZfrvYltGd9L1GUhBAv439Km279O5DU/reply", $access_token['access_token'], $postBody);
                         
        }
        
        return view("/dashboard", compact('reviews'));
    }
    
    
    /**
     * Get Reviews  
     * @param Request accountid, locationid, postbody     
     * @return  response 
    */ 
    public function gmbreviews()
    {
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
        
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);    
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

           $reviews = $this->Google_my_business->get_reviews('accounts/116775430050800689419/locations/2093285886886357603/reviews', $access_token['access_token']);
            
        }        
        return view("/dashboard", compact('reviews'));
    }
    
    /**
     * Get Posts  
     * @param Request accountid, locationid    
     * @return  response 
    */
    public function gmbposts()
    {
        
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);    
            
             session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    

            $posts = $this->Google_my_business->get_local_post('accounts/116775430050800689419/locations/2093285886886357603/localPosts', $access_token['access_token']);
            //echo '<PRE>'; print_r($posts);exit;
            
        }
        return redirect('/home');        
    }
    
    
    /**
     * Create Location Group  
     * @param Request $request    
     * @return  response 
    */
    public function gmbcreategroup(Request $request)
    {
        $GroupName =  $request->GroupName;
        
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);
       
        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);            
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);   
            
            foreach($accounts['accounts'] as $acc_arr){
                if($acc_arr['type']=='PERSONAL'){                    
                    $primaryOwner = $acc_arr['name'];
                };
            }
            
            $postBody = array (
                        'permissionLevel' => 'OWNER_LEVEL',
                        'accountName' => $GroupName,
                        'state' => 
                        array (
                          'status' => 'UNVERIFIED',
                        ),
                        'role' => 'OWNER',
                        'type' => 'LOCATION_GROUP',
                      );

            

            $response = $this->Google_my_business->create_group_location('accounts', $access_token['access_token'], $postBody, $primaryOwner);
                       
            if(isset($response['error']) ){
                return response()->json(['error'=>$response['error']['message']]);
            }else{
                return response()->json(['success'=>'Group Created Successfully']);
            }            
        }    
        
    }
    
    ////////////////////////////////////////////////////////////////// 
    
    /**
     * Get accountdetails by accountid  
     * @param Request $request    
     * @return  array 
    */
    
    public function accountdetails(Request $request)
    {
       $accountid =  $request->accountid;         
        $account_details = array();
        $access_token = $this->chktoken();
       
        if(is_object($access_token) ){ return redirect('/dashboard'); }   
        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);      

        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {
            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);           
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);    
            $accounts = "accounts/".$accountid;
            $account_details = $this->Google_my_business->get_account_details_by_accountid($accounts, $access_token['access_token']);
   
        }     
        
        if(isset($account_details['error']) ){
            return response()->json(['error'=>$response['error']['message']]);
        }else{
            return response()->json(['success'=>$account_details]);
        }
               
    }
    
    
     /**
     * Edit  Location Group
     * @param Request $request    
     * @return  array 
    */
    public function gmbeditgroup(Request $request)
    {
        $GroupName =  $request->GroupName; 
        $accountid =  $request->accountid;
       
        $access_token = $this->chktoken();
        if(is_object($access_token) ){ return redirect('/dashboard'); }   

        $accounts = $this->Google_my_business->get_accounts($access_token['access_token']);

        if(isset($accounts['accounts']) && count($accounts['accounts']) > 0)
        {            
            session(['gmb_account_name' => $accounts['accounts'][0]['name']]);            
            session(['gmb_user_name' => $accounts['accounts'][0]['accountName']]);   
                       
            $postBody = array (
                        'permissionLevel' => 'OWNER_LEVEL',
                        'name' => 'accounts/'.$accountid,
                        'accountName' => $GroupName,
                        'state' => 
                        array (
                          'status' => 'UNVERIFIED',
                        ),
                        'role' => 'OWNER',
                        'type' => 'LOCATION_GROUP',
                      );
            
            $response = $this->Google_my_business->edit_group_location('accounts/'.$accountid, $access_token['access_token'], $postBody);
                       
            if(isset($response['error']) ){
                return response()->json(['error'=>$response['error']['message']]);
            }else{
                return response()->json(['success'=>'Group Edited Successfully']);
            }            
        }
    }
    
}

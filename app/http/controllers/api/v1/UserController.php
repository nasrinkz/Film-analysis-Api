<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SmsIR_UltraFastSend;
use App\Models\ActivationCode;
use App\Models\Category;
use App\Models\Film;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'mobileNumber' => 'required|iran_mobile'
        ]);

        if ($validator->fails()) {
            return $this->_response('فرمت شماره موبایل صحیح نمی باشد', -1, []);
        }

        try {
            $mobile_number = $request->get('mobileNumber');
            $user = User::whereMobileNumber($mobile_number)->first();

            if (!$user) {
                $user = new User();
            }

            if($user->main_status == 'gheire_faal'){
                return $this->_response('حساب کاربری شما هنوز فعال نشده است.', -2, []);
            }
            if($user->main_status == 'masdood'){
                return $this->_response('حساب کاربری شما مسدود است.', -3, []);
            }

            $verify_code = rand(1000, 9999);
            $user->mobile_number = $mobile_number;
            $user->save();
            $activation_code = new ActivationCode();
            $activation_code->user_id = $user->id;
            $activation_code->code = $verify_code;
            $activation_code->status = 0;
            $activation_code->save();

            try {
                $data = array(
                    "ParameterArray" => array(
                        array(
                            "Parameter" => "VerificationCode",
                            "ParameterValue" => $verify_code
                        ),
                    ),
                    "Mobile" => $user->mobile_number,
                    "TemplateId" => "64753"
                );
                $SmsIR_UltraFastSend = new SmsIR_UltraFastSend();
                $UltraFastSend = $SmsIR_UltraFastSend->ultraFastSend($data);
                //ارسال پیامک با موفقیت انجام شد
                return $this->_response('پیام ارسال شد', 1, []);
            } catch (\Exeption $e) {
                //مشکل در ارسال پامک
                return $this->_response('اشکال در ارسال اس ام اس', -4, []);
            }


        } catch (\Exception $e) {
            return $this->_response('خطایی سمت سرور اتفاق افتاده است', -5, []);


        }
    }

    public function validateCode(Request $request){
        $validator = Validator::make($request->all(), [
            'mobileNumber' => 'required',
            'code' => 'required|max:4|min:4',
        ]);

        if ($validator->fails()) {

            return $this->_response('اطلاعات ضروری را ارسال کنید', -1,[]);
        }
        try {
            $mobileNumber =  $request->get('mobileNumber');
            $user = User::whereMobileNumber($mobileNumber)->first();
            $user_id = $user->id;
            $code =  $request->get('code');
            $validation = ActivationCode::whereUserId($user_id)->whereCode($code)->whereStatus(0)->first();
            if($validation) {
                $validation->status = 1;
                $validation->save();
                $films = Film::with(['category','directors','genres','actors','authors','producers','keyWords'])->latest()->get();
                return $this->_response('کد تایید شد', 1,[
                    'user'=>$user,
                    'films'=>$films,
                    'genres'=>Genre::latest()->get(),
                    'categories'=>Category::latest()->get(),
                ]);
            }
            else {
                return $this->_response('کد وارد شده صحیح نمی باشد.', -2,[]);
            }
        } catch (\Exception $e) {
            return $this->_response('خطایی سمت سرور اتفاق افتاده است', -3,[]);
        }
    }

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'userId' => 'required|max:100',
            'name' => 'required|min:3|max:80',
            'family' => 'required|min:3|max:80',
        ]);

        if ($validator->fails()) {
            return $this->_response('اطلاعات ضروری را ارسال کنید', -1, []);
        }

        try {
            $user_id = $request->get('userId');
            $name =$request->get('name');
            $family = $request->get('family');
            $field_of_study = $request->get('fieldOfStudy');
            $degree_of_study = $request->get('degreeOfStudy');
            $email = $request->get('email');

            $user = User::find($user_id);
            if($user){
                $user->name = $name;
                $user->family = $family;
                $user->field_of_study =$field_of_study;
                $user->degree_of_study=$degree_of_study;
                $user->email=$email;
                $user->register_status='takmil_shode';
                $user->main_status='faal';
                $user->api_token=Str::random(60);
                $user->save();
                return $this->_response('با موفقیت انجام شد', 1, [
                    'user' => $user
                ]);

            }else{
                return $this->_response('این کاربر یافت نشد', -2, []);

            }

        }catch (\Exception $e){
            return $this->_response('خطایی سمت سرور اتفاق افتاده است', -3, []);

        }
    }

    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'apiToken' => 'required|max:100',
            'name' => 'required|min:3|max:80',
            'family' => 'required|min:3|max:80',
        ]);

        if ($validator->fails()) {
            return $this->_response('اطلاعات ضروری را ارسال کنید', -1, []);
        }

        try {
            $apiToken = $request->get('apiToken');
            $name =$request->get('name');
            $family = $request->get('family');
            $field_of_study = $request->get('fieldOfStudy');
            $degree_of_study = $request->get('degreeOfStudy') == "مدرک تحصیلی" ? null  : $request->get('degreeOfStudy');

            $user = User::whereApiToken($apiToken)->first();

            if($user->main_status == 'gheire_faal'){
                return $this->_response('حساب کاربری شما هنوز فعال نشده است.', -2, []);
            }
            if($user->main_status == 'masdood'){
                return $this->_response('حساب کاربری شما مسدود است.', -3, []);
            }


            if($user){
                $user->name = $name;
                $user->family = $family;
                $user->field_of_study =$field_of_study ;
                $user->degree_of_study=$degree_of_study;
                $user->save();
                return $this->_response('با موفقیت انجام شد', 1, [
                    'user' => $user
                ]);

            }else{
                return $this->_response('این کاربر یافت نشد', -4, []);

            }

        }catch (\Exception $e){
            return $this->_response('خطایی سمت سرور اتفاق افتاده است', -5, []);

        }
    }

    private function _response(string $message, int $statusCode, array $data)
    {
        $response = [
            'message' => $message,
            'statusCode' => $statusCode,
        ];
        if (count($data) != 0) {
            $response = [
                'message' => $message,
                'statusCode' => $statusCode,
                'responseData' => $data,
            ];
        }
        return response()->json($response, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8']);
    }

}

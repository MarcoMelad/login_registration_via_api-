<?php

namespace App\Http\Controllers;

use App\Events\VerifyEmailByCode;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Register;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     */
    public function activeCode(Request $request)
    {
        $request-> validate([
            'code_type'=> 'required|in:email,mobile',
            'code'=> 'required|integer'
        ]);
        if ($request->code_type == 'mobile'){

        }elseif ($request->code_type == 'email'){
            if ($request->code == authApi()->user()->email_code){
                $user = authApi()->user();
                $user->email_code = null;
                $user->email_verified_at=now();
                $user->save();
                $message = __('main.email_active_successfully');
            }else {
                $message = __('main.code_wrong');
            }
        }
        return res_data([],$message);
    }


    public function resendActiveCode(Request $request)
    {
        $dtat = $request-> validate(['code_type'=> 'required|in:email,mobile']);
        if ($request->code_type == 'mobile'){

        }elseif ($request->code_type == 'email'){
            event(new VerifyEmailByCode(User::find(authApi()->id())));
        }
        return res_data([],__('main.code_sent_to_mail'));
    }

    public function register(Register $request)
    {
        $data= $request->validated();
        $data['password'] = bcrypt($request->password);
        $data['mobile'] = ltrim($request->mobile,'0');
        $data['email_code'] = rand(00000,99999);
        $data['mobile_code'] = rand(00000,99999);;


        $user = User::create($data);

        $credentials = ['email'=>$request->email,'password'=>$request->password];
//        if (! $token = authApi()->attempt()) {
//            return response()->json(['error' => 'Unauthorized'], 401);
//        }
//        return $this->respondWithToken($token);
        return $this->login($credentials);
    }
    public function login(array $cred = null)
    {
        $credentials = [
            'password'=> request('password')
        ];
        if  (filter_var(request('account'),FILTER_VALIDATE_EMAIL)){
            $credentials['email'] = request('account');
        }elseif(intval(request('account'))) {
            $credentials['mobile'] = ltrim(request('account'),'0');

        }

        $attemp = !empty($cred)? $cred:$credentials;
        if (! $token = authApi()->attempt($attemp)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = [];
        $data['token'] = $this->respondWithToken($token)?->original;
        $data['need_mobile_verified'] = authApi()-> user()-> mobile_verified_at == null;
        $data['need_email_verified'] = authApi()-> user()-> email_verified_at == null;

        return res_data($data,__('main.login_msg'));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user =authApi()->user()->only('id','name','email','mobile');

        return res_data($user);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        authApi()->logout();

//        return response()->json(['message' => 'Successfully logged out']);
        return res_data([],__('main.logout_msg'));
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = $this->respondWithToken(authApi()->refresh());
        return res_data($token);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => authApi()->factory()->getTTL() * 99999
        ]);
    }
}

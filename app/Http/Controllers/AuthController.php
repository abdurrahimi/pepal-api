<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\Helper;
use App\Models\User;
use App\Jobs\Email;

class AuthController extends Controller
{

    use Helper;
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'logout','loginMember','register','step1','activate','forgot','forgot_password']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return Response
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email',$request->email)->first();
        if(empty($user)){
            return response()->json(['message' => 'user tidak ditemukan'],401);
        }

        if($user->email_verified_at == null){
            return response()->json(['message' => 'Akun anda belum di aktivasi'],431);
        }

        if($user->is_login_otp == 1){
            if($request->otp !== $user->otp || date("Y-m-d H:i:s") > $user->otp_valid_time){
                return response()->json(['message' => 'otp tidak valid atau sudah kadaluarsa'],400);
            }

            $credentials = $request->only(['email', 'password', 'otp']);
        }else{
            $credentials = $request->only(['email', 'password', 'otp']);
        }

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function step1(Request $request){
        //echo 'xxx'; die;
        //return $request->all();
        $user = User::where('email','=',$request->data['email'])->first();
        //return $user;
        if(empty($user)){
            return response()->json(['message' => 'user tidak ditemukan'],401);
        }
        
        if($user->email_verified_at == null){
            return response()->json(['message' => 'Akun anda belum di aktivasi'],431);
        }

        if($user->is_login_otp == 1){
            $otp = rand(100000, 999999);
            $time = date("Y-m-d H:i:s", strtotime('+2 hours'));
            $model = User::find($user->id);
            $model->otp = $otp;
            $model->otp_valid_time = $time;
            $model->save();
            Email::dispatch('OTP',$request->data['email'])->onQueue('email');
            return response()->json(['otp' => true]);
        }
        return response()->json(['otp' => false]);

    }

    public function profile()
    {
        return response()->json(['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $user->name = $request->name;
        if (preg_match('/^data:image\/(\w+);base64,/', $request->profile_pict)) {
            $image = $this->storeImageLocal($request->profile_pict);
            $user->profile_pict = $image;
        }else{
            $user->profile_pict = $request->profile_pict;
        }
        $user->profesi = $request->profesi;
        $user->alamat = $request->alamat;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->save();
        return response()->json(['user' => $user]);
    }

    public function changePassword(Request $request)
    {
        $user = User::find(auth()->user()->id);
        if(!Hash::check($request->old_pass, $user->password)){
            return response()->json(['message' => 'password lama tidak sesuai'],400);
        }
        
        if($request->password != $request->password_confirm){
            return response()->json(['message' => 'password tidak sama'],400);
        }

        if(strlen($request->password) < 6){
            return response()->json(['message' => 'password setidaknya harus 6 character'],400);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        
        return response()->json(['message' => 'success']);
    }

    public function setting(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $user->is_login_otp = $request->otp;
        $user->is_verif_sandi_order = $request->verif;
        $user->save();
    }

    public function loginMember(Request $request)
    {
        //return response()->json(['email' => $request->all()]);
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['email', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'name'  => 'required|string',
        ]);
        $bytes = random_bytes(20);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email_verified_token' => bin2hex($bytes),
            'roles'  => 'member',
        ]);
        Email::dispatch('AKTIVASI',$request->email)->onQueue('email');
        return response()->json(['message' => 'success']);
    }

    public function forgot(Request $request)
    {
        //return $request->all();
        $bytes = random_bytes(20);
        User::where('email',$request->email)->update([
            'forgot_token' => bin2hex($bytes),
        ]);

        Email::dispatch('FORGOT_PASSWORD',$request->email);

        return response()->json(['message' => 'success']);
    }

    public function forgot_password(Request $request)
    {
        $update = User::where('email',$request->email)->where('forgot_token',$request->token)->update([
            'password' => Hash::make($request->password)
        ]);
        
        return response()->json(['message' => 'success']);
    }

    public function activate(Request $request)
    {
        $user = User::where('email',$request->input('email'))->where('email_verified_token', $request->input('token'))->first();

        if(empty($user)){
            return view("notfound");
        }

        $model = User::find($user->id);
        $model->email_verified_at = date('Y-m-d H:i:s');
        $model->save();
        return view("success");
    }

     /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(['name' => auth()->user()->name, 'email' => auth()->user()->email,'profile' => auth()->user()->profile_pict, 'role' => auth()->user()->roles,'profesi' => auth()->user()->profesi]);
    }


    public function profilePicture(Request $request)
    {
        $this->validate($request, [
            'picture' => 'required|string',
        ]);

        $user = User::find(Auth::user()->id);
        $user->profile_pict = $request->picture;
        $user->save();
        return response()->json(['message' => 'success']);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
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
            'expires_in' => auth()->factory()->getTTL() * 60 * 24
        ]);
    }
}
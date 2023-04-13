<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'logout','loginMember','register']]);
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

        $credentials = $request->only(['email', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function profile()
    {
        return response()->json(['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $user->name = $request->name;
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
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'roles'  => 'member',
        ]);
        
        return response()->json(['message' => 'success']);
    }

     /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(['name' => auth()->user()->name, 'email' => auth()->user()->email,'profile' => auth()->user()->profile_pict, 'role' => auth()->user()->roles]);
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
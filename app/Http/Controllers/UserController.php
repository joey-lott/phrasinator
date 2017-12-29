<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Validation\Rule;
use App\EtsyApiKey;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ["only" => ["login", "create", "store"]]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
      $credentials = $request->only("email", 'password');
      $response = $this->guard()->attempt($credentials, $request->filled('remember'));
      if($response) { return redirect('dashboard'); }
      else { return redirect(''); }
    }

    public function create() {
      return "Registration is closed";
      return view('auth.register');
    }

    public function store(Request $request) {
      return "Registration is closed";
      $this->validate($request, [
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
        'registrationPassword' => ['required', Rule::in(["abczxy"])]
      ]);
      $user = User::create(["email" => $request->email, "password" => bcrypt($request->password)]);
      $this->guard()->login($user);
      return redirect('/home');
    }

    public function etsyApiKey() {
      return view('auth.apikey');
    }

    public function etsyApiKeySubmit(Request $request) {
      $key = new EtsyApiKey();
      $key->key = $request->key;
      $key->secret = $request->secret;
      $key->user_id = auth()->user()->id;
      $key->save();
      return redirect('/home');
    }

    public function etsyAuthorize() {
      $user = new User;
      $user->email = "email";
      $user->password = "password";
      $api = resolve("\App\Etsy\EtsyAPI");
      $etsyLink = $api->getEtsyAuthorizeLink();
      return view('auth.authorizeEtsy', ["etsyLink" => $etsyLink]);
    }

    public function completeAuthorization(Request $request) {
      $tokenSecret = $_COOKIE['token_secret'];
      $token = $_GET['oauth_token'];
      $verifier = $_GET['oauth_verifier'];
      $api = resolve("\App\Etsy\EtsyAPI");
      $response = $api->finalizeAuthorization($tokenSecret, $token, $verifier);
      if($response) {
        $user = auth()->user();
        return redirect("/home");
      }
      else {
        return redirect("/home")->with("state", "etsyAuthRetry");
      }
    }

    private function guard() {
      return \Auth::guard();
    }
}

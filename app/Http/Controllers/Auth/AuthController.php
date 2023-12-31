<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Shop;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\UnauthorizedException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('token', ['except' => ['login', 'register']]);

    }

    /**
     * Get a JWT via given credentials
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request) {

//        $request->session()->regenerate();

        $credential = $request->only('username', ('password'));

        if (! $token = auth()->attempt($credential)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
//        Cache::driver('redis')->put('user:profile', auth()->user(), 60);
//        Cache::put('123', 'check', 60);
//        //break line
//
//        Redis::set('test', auth()->user(), 'EX', 60);
//        //get
//        $check = Cache::get('123');
//        print_r("$check\n");
//        $user = Redis::get('test');
//        print_r($user);

//        Redis::pipeline(function (Redis $redis) {
//            for ($i = 0; $i < 1000; $i++) {
//                $redis->set("key:$i", $i);
//            }
//        });
//        $redisKeys = Redis::keys('*');
//        print_r($redisKeys);

        Cache::remember('users', 60, function () {
            return auth()->user();
        });

        $check = Cache::get('users');
        print_r("$check\n");

        return $this->createNewToken($token);
    }

    /**
     * Register a new user
     *
     * @return JsonResponse
     */
    public function register(RegisterRequest $request) {
        $request['password'] = bcrypt($request['password']);
        $user_data = [
            'username' => $request->username,
            'password' => $request->password,
            'role' => $request->role,
        ];


        $user = User::create($user_data);

        $user_id = $user->id;

        $shop_data = [
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'user_id' => $user_id
        ];

        $shop = Shop::create($shop_data);

        return response()->json([$user, $shop], 201);
    }

    /**
     * Log the user out
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function logout() {

        try {
            auth()->logout();

            return response()->json(
                ['message'=> 'Logout success!']
            );
        }
        catch (\Exception $exception) {
            return response()->json(
                ['message'=> 'Logout failed!']
            );
        }

    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated user
     *
     * @return JsonResponse
     */
    public function userProfile() {
        if (Cache::store('redis')->has('user:profile')) {
            $user = Cache::store('redis')->get('user:profile');
            print_r("$user\n");
        }
        if(Redis::exists('user')) {
            print "user from redis";
            $user = Redis::get('user');
//            Redis::del('user:profile');
        }
        else {
            print "user from db";
            $user = auth()->user();
//            Redis::set('user:profile', $user);
        }

        return response()->json($user);
    }

    /**
     * Get new token
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    public function createNewToken(string $token) {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }



}

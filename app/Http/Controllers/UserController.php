<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountUpdateRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Favorites;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function authSanctum(Request $request)
    {
        return $request->user();
    }

    public function login(LoginRequest $request)
    {

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $request->session()->regenerate();

            return true;
        }
        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
        //Auth::login($user);

    }

    public function logout(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return true;
    }

    public function register(RegisterRequest $request)
    {
        $date = date_create($request->date)->format('Y:m:d');
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'birthdate' => $date,
            'country' => $request->country,
            'phone' => $request->phone,
        ]);
        Auth::login($user);
        return true;
    }

    /**
     * Determines if user authenticated 
     *
     * @return bool
     */
    public static function isAuth()
    {
        return Auth::guard('web')->user() ? true : false;
    }

    /**
     * Determines if user can edit selected conference
     *      
     * @return bool
     */
    public static function canEdit($user, $confId = null)
    {
        if (!$user) {
            return false;
        }

        if ($user->role == 'announcer') {
            if ($confId === null) {
                return true;
            }
            //Id`s of all conferences created by user
            $confs = User::join('conferences', 'users.id', '=', 'conferences.user_id')->select('conferences.id')->where('users.id', $user->id)->get();

            $canEdit = false;
            foreach ($confs as $conf) {
                if ($conf->id == $confId) {
                    $canEdit = true;
                }
            }

            return $canEdit;
        } elseif ($user->role == 'admin') {
            return true;
        }
        return false;
    }

    /**
     * Determines if user can add conference
     *
     * @return bool
     */
    public static function canReportEdit($user, $repId)
    {
        if (!$user) {
            return false;
        }

        if ($user->role == 'admin') {
            return true;
        } else {
            //Id`s of all conferences created by user
            $reports = User::join('reports', 'users.id', '=', 'reports.user_id')->select('reports.id')->where('users.id', $user->id)->get();

            $canEdit = false;
            foreach ($reports as $report) {
                if ($report->id == $repId) {
                    $canEdit = true;
                }
            }

            return $canEdit;
        }
    }

    /**
     * Determines if user can add conference
     *
     * @return bool
     */
    public function show()
    {
        $user = Auth::user();
        if (!Auth::user()) {
            abort(403);
        }

        return json_encode([
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'password' => '',
            'currentPassword' => '',
            'date' => $user->birthdate,
            'country' => $user->country,
            'phone' => $user->phone,
        ]);
    }

    /**
     * Determines if user can add conference
     *
     * @return bool
     */
    public function update(AccountUpdateRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        if (!Hash::check($request->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => 'The provided password do not match our records.',
            ]);
        }
        if ($user->email != $request->email && User::where('email', $request->email)->get()->count() > 0) {
            throw ValidationException::withMessages([
                'email' => 'The email must be unique.',
            ]);
        }

        $date = date_create($request->date)->format('Y:m:d');
        if ($request->password) {
            if (strlen($request->password) < 6) {
                throw ValidationException::withMessages([
                    'password' => 'Minimum 6 characters',
                ]);
            }
            $values = [
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'birthdate' => $date,
                'country' => $request->country,
                'phone' => $request->phone,
            ];
        } else {
            $values = [
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'birthdate' => $date,
                'country' => $request->country,
                'phone' => $request->phone,
            ];
        }

        return User::where('id', $user->id)->update($values);
    }

    /**
     * Returns favorites count
     *
     * @return int
     */
    public function favoritesCount()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        return Favorites::where('user_id', $user->id)->count();
    }

    /**
     * Returns user id
     *
     * @return int
     */
    public function getUserId()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        return $user->id;
    }

    /**
     * Determines user privileges 
     *
     * @return Object
     */
    public function getPerks()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $perks = [];
        if ($user->role == 'admin') {
            $perks['isAdmin'] = true;
        }
        if ($user->role == 'announcer' || $user->role == 'admin') {
            $perks['canAdd'] = true;
        }

        return json_encode($perks);
    }

     /**
     * Determines user privileges 
     *
     * @return Object
     */
    public function index()
    {
        return view('login');
    }
}

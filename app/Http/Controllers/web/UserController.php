<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function data()
    {
        return DataTables::of(User::query()->orderBy('created_at', 'desc'))->toJson();
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {

            $data = User::orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', 'web.layouts.button.user-button')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('web.user.index');
    }

    public function create()
    {
        $userGroups = UserGroup::all();
        // return $userGroup;

        return view('web.user.create', [
            'user_groups' => $userGroups,
        ]);
    }

    public function store(Request $request)
    {

        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:25',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'user_group_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(), // ambil error pertama
            ], 422);
        }

        // Valid, proceed menyimpan data
        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->password2 = $request->password2;
        $user->mobile_access = $request->mobile_access;
        $user->user_group_id = $request->user_group_id;
        $user->created_by = 1;
        $user->save();

        // User::create([
        //     'name' => $request->name,
        //     'username' => $request->username,
        //     'email' => $request->email,
        //     'password' => bcrypt($request->password),
        //     'password2' => $request->password,
        //     'mobile_access' => $request->mobile_access,
        //     'user_group_id' => $request->user_group_id,
        //     'created_by' => 1,
        // ]);

        return response()->json(['success' => true, 'message' => 'User created successfully']);
    }
}

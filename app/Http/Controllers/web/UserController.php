<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Exception;
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

            $users = User::orderBy('created_at', 'desc');

            if ($request->has('search_all') && $request->search_all != '') {
                $search = $request->search_all;

                $users->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            return DataTables::of($users)
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
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->email_verified_at = Carbon::now();
        $user->password = $request->password;
        $user->password2 = $request->password_confirmation;
        $user->mobile_access = $request->mobile_access;
        $user->user_group_id = $request->user_group_id;
        $user->created_by = 1;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User created successfully']);
    }

    public function edit($id)
    {
        $userGroups = UserGroup::all();
        $user = User::find($id);
        // return $userGroup;

        return view('web.user.edit', [
            'user_groups' => $userGroups,
            'user' => $user,
        ]);
    }

    public function update($id, Request $request)
    {

        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:25',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password' => 'required|string|min:8|confirmed',
            'user_group_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::find($id);
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->email_verified_at = Carbon::now();
        $user->password = $request->password;
        $user->password2 = $request->password_confirmation;
        $user->mobile_access = $request->mobile_access;
        $user->user_group_id = $request->user_group_id;
        $user->created_by = 1;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User updated successfully']);
    }


    public function destroy(string $id)
    {
        try {
            $user = User::find($id);
            $user->delete();
            return [
                'message' => 'data has been deleted',
                'error' => false,
                'code' => 200,
            ];
        } catch (Exception $e) {
            return [
                'message' => 'internal error',
                'error' => true,
                'code' => 500,
                'errors' => $e->getMessage(),
            ];
        }
    }
}

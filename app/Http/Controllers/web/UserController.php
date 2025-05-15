<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
}

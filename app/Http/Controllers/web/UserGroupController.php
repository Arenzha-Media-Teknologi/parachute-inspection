<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userGroup = UserGroup::orderBy('id', 'desc')->get();

        return view('web.user-group.index', [
            'userGroup' => $userGroup,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = [
            [
                'header' => 'Master Data',
                'subheaders' => [
                    [
                        'name' => 'Parasut',
                        'value' => 'parachute',
                        'items' => ['view', 'add', 'edit', 'delete'],
                        'type' => 'sub',
                    ],
                    [
                        'name' => 'Pemeriksaan Parasut',
                        'value' => 'parachute_check',
                        'items' => ['view', 'add', 'edit', 'delete'],
                        'type' => 'sub',
                    ],
                    [
                        'name' => 'User',
                        'value' => 'user',
                        'items' => ['view', 'add', 'edit', 'delete'],
                        'type' => 'sub',
                    ],
                    [
                        'name' => 'User Group',
                        'value' => 'user_group',
                        'items' => ['view', 'add', 'edit', 'delete'],
                        'type' => 'sub',
                    ],
                ],
            ],
            [
                'header' => 'Laporan',
                'type' => 'sub',
                'subheaders' => [
                    [
                        'name' => 'Laporan',
                        'value' => 'report',
                        'items' => ['view'],
                        'type' => 'header_sub',
                    ],
                    [
                        'name' => 'laporan data Parasurut',
                        'value' => 'report_parachute_data',
                        'items' => ['view'],
                        'type' => 'sub',
                    ],

                    [
                        'name' => 'Laporan Pemeriksaan Parasut',
                        'value' => 'report_parachute_check',
                        'items' => ['view'],
                        'type' => 'sub',
                    ],
                ],
            ],
            [
                'header' => 'Pengaturan',
                'subheaders' => [ // Pegawai
                    [
                        'name' => 'Pengaturan',
                        'value' => 'setting',
                        'items' => ['view', 'add', 'edit', 'delete'],
                        'type' => 'sub',
                    ],
                ],
            ],

        ];

        return view('web.user-group.create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $newUserGroup = new UserGroup();
            $newUserGroup->name = $request->name;
            $newUserGroup->permissions = $request->permissions;
            $newUserGroup->save();

            DB::commit();
            return response()->json([
                'message' => 'Data has been saved',
                'data' => $newUserGroup,
                'code' => 200,
                'error' => false,
            ]);
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 500,
                'error' => true,
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

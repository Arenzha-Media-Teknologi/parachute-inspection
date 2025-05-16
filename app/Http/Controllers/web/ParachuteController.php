<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Parachute;
use App\Services\ParachuteImportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ParachuteController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected $importService;

    public function __construct(ParachuteImportService $importService)
    {
        $this->importService = $importService;
    }

    public function indexData(Request $request)
    {
        $parachute = Parachute::orderBy('id', 'desc');
        $filter = $request->number;


        if (isset($filter)) {
            $parachute = $parachute->where('serial_number', 'like', '%' . $filter . '%')
                ->orWhere('type', 'like', '%' . $filter . '%')
                ->orWhere('part_number', 'like', '%' . $filter . '%');
        }

        return DataTables::eloquent($parachute)
            ->addIndexColumn()
            ->addColumn('action', 'web.layouts.button.parachute-button')
            ->rawColumns(['action'])
            ->make(true);
    }


    public function index()
    {
        $parachute = Parachute::orderBy('id', 'desc')->get();
        return view('web.parachute.index', [
            'parachute' => $parachute,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $newParachute = new Parachute();
            $newParachute->serial_number = $request->serialNumber;
            $newParachute->type = $request->type;
            $newParachute->category = $request->category;
            $newParachute->part_number = $request->partNumber;
            $newParachute->save();

            DB::commit();
            return response()->json([
                'message' => 'Data has been saved',
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
        DB::beginTransaction();
        try {
            $updateParachute = Parachute::find($id);
            $updateParachute->serial_number = $request->serialNumber;
            $updateParachute->category = $request->category;
            $updateParachute->type = $request->type;
            $updateParachute->part_number = $request->partNumber;
            $updateParachute->save();

            DB::commit();
            return response()->json([
                'message' => 'Data has been saved',
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

    public function showImportForm()
    {
        return view('web.parachute.import', []);
    }



    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $file = $request->file('excel_file');
            $filePath = $file->getRealPath();

            $importService = new ParachuteImportService();
            $result = $importService->import($filePath);
            return back()->with('success', 'Berhasil mengimpor ' . count($result) . ' data');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids');

        try {
            Parachute::whereIn('id', $ids)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            $parachute = Parachute::find($id);
            $parachute->delete();
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

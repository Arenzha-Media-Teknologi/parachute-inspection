<?php

namespace App\Http\Controllers;

use App\Models\Parachute;
use App\Models\ParachuteInspection;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ParachuteInspectionController extends Controller
{
    public function indexData(Request $request)
    {
        $query = ParachuteInspection::with('parachute')->orderBy('id', 'desc');
        $filter = $request->number;
        if (!empty($filter)) {
            $query->where(function ($q) use ($filter) {
                $q->where('number', 'like', '%' . $filter . '%')
                    ->orWhereHas('parachute', function ($p) use ($filter) {
                        $p->where('type', 'like', '%' . $filter . '%')
                            ->orWhere('part_number', 'like', '%' . $filter . '%')
                            ->orWhere('serial_number', 'like', '%' . $filter . '%');
                    });
            });
        }

        if ($request->filled('type')) {
            $query->whereHas('parachute', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->type . '%');
            });
        }
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->filled('date_start')) {
            $query->where('date', '>=', $request->date_start);
        } elseif ($request->filled('date_end')) {
            $query->where('date', '<=', $request->date_end);
        }
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('category', function ($inspection) {
                return $inspection->parachute->category ?? '-';
            })
            ->addColumn('type', function ($inspection) {
                return $inspection->parachute->type ?? '-';
            })
            ->addColumn('part_number', function ($inspection) {
                return $inspection->parachute->part_number ?? '-';
            })
            ->addColumn('serial_number', function ($inspection) {
                return $inspection->parachute->serial_number ?? '-';
            })
            ->addColumn('action', 'web.layouts.button.parachute-inspection-button')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function index()
    {
        // return view('web.parachute-inspection.index');
        $parachuteInspection = ParachuteInspection::with('parachute')->orderBy('id', 'desc')->get();
        $parachute = Parachute::orderBy('id', 'desc')->get();
        return view('web.parachute-inspection.index', [
            'parachute_inspection' => $parachuteInspection,
            'parachute' => $parachute,
        ]);
    }

    public function store(Request $request)
    {
        // $user_id = Auth::user()->id;
        $validated = $request->validate([
            'code' => 'required|unique:parachute_inspections,number',
            'date' => 'required|date',
            'activity' => 'nullable|string|max:255',
            'checker' => 'nullable|string|max:255',
            'parachute_id' => 'required|exists:parachutes,id',
        ]);
        // return $request->all();
        try {
            DB::beginTransaction();
            $inspection = ParachuteInspection::create([
                'number' => $validated['code'],
                'date' => $validated['date'],
                'activity_name' => $validated['activity'],
                'person_in_charge' => $validated['checker'],
                'parachute_id' => $validated['parachute_id'],
                // 'created_by' => $user_id,
            ]);
            if ($request->has('items')) {
                foreach ($request->items as $index => $item) {
                    if (isset($item['file']) && $item['file']) {
                        $file = $request->file("items.$index.file");
                        $filePath = $file->store('parachute-files', 'public');
                        $inspection->items()->create([
                            'description' => $item['description'] ?? null,
                            'image_url' => $filePath,
                            'image_file_name' => $file->getClientOriginalName(),
                            'image_file_size' => $file->getSize(),
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'Data berhasil disimpan',
                'data' => $inspection
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $parachute = ParachuteInspection::find($id);
            $parachute->delete();
            return [
                'message' => 'Data telah dihapus',
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

    public function generateCode()
    {
        $today = Carbon::today();
        $lastCode = DB::table('parachute_inspections')
            ->whereDate('created_at', $today)
            ->orderByDesc('number')
            ->value('number');
        $nextNumber = 1;
        if ($lastCode) {
            $lastNumber = (int) substr($lastCode, -3);
            $nextNumber = $lastNumber + 1;
        }
        $dateStr = $today->format('dmY');
        $padded = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $newCode = "PR-{$dateStr}-{$padded}";

        return response()->json(['code' => $newCode]);
    }
}

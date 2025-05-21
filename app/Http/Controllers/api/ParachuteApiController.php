<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Parachute;
use Illuminate\Http\Request;

class ParachuteApiController extends Controller
{
    public function getAll(Request $request)
    {
        try {
            $parachuteInspections = [];
            $isPagination = $request->query('pagination') ?? false;
            $startDate = request()->query('start_date');
            $endDate = request()->query('end_date');
            $type = request()->query('type');
            $searchKeyword = request()->query('search');

            if (isset($isPagination)) {
                if ($isPagination == 'true' || $isPagination == '1') {
                    $isPagination = true;
                }
            }

            $parachuteInspectionQuery = Parachute::query();

            if (isset($type)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->whereRelation('parachute', 'type', $type);
            }

            if (isset($startDate) && isset($endDate)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->whereBetween('date', [$startDate, $endDate]);
            }

            if (isset($searchKeyword)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->where(function ($query) use ($searchKeyword) {
                    // Search in main table
                    $searchableColumns = ['serial_number', 'part_number', 'type', 'category'];
                    $searchTerm = strtolower($searchKeyword);

                    foreach ($searchableColumns as $column) {
                        $query->orWhereRaw("LOWER({$column}) LIKE ?", ['%' . $searchTerm . '%']);
                    }
                });
            }

            $parachuteInspectionQuery = $parachuteInspectionQuery->orderBy('created_at', 'DESC');

            if (!$isPagination) {
                $parachuteInspections = $parachuteInspectionQuery->limit(10)->get();
            } else {
                $limit = $request->query('limit') ?? 10;
                // $page = $request->query('page') ?? 1;
                $parachuteInspections = $parachuteInspectionQuery->paginate($limit)->withQueryString();
            }

            return response()->json([
                'message' => 'OK',
                'data' => $parachuteInspections,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\ParachuteInspection;
use App\Models\ParachuteInspectionItem;
use App\Models\ParachuteInspectionItemDescription;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ParachuteInspectionApiController extends Controller
{
    public function getAll(Request $request)
    {
        try {
            $parachuteInspections = [];
            $isPagination = $request->query('pagination') ?? false;
            $startDate = request()->query('start_date');
            $endDate = request()->query('end_date');
            $type = request()->query('type');
            $category = request()->query('category');
            $repairStatus = request()->query('repairStatus');
            $searchKeyword = request()->query('search');

            if (isset($isPagination)) {
                if ($isPagination == 'true' || $isPagination == '1') {
                    $isPagination = true;
                }
            }

            $parachuteInspectionQuery = ParachuteInspection::with(['parachute', 'items.descriptions', 'createdByUser', 'updatedByUser']);

            if (isset($type)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->whereRelation('parachute', 'type', $type);
            }

            if (isset($category)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->whereRelation('parachute', 'category', $category);
            }

            if (isset($startDate) && isset($endDate)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->whereBetween('date', [$startDate, $endDate]);
            }

            if (isset($repairStatus) && $repairStatus !== '') {
                if ($repairStatus === 'serviceable') {
                    // Filter for inspections where ALL items have status = 1
                    $parachuteInspectionQuery = $parachuteInspectionQuery->whereDoesntHave('items', function ($query) {
                        $query->where('status', '!=', 1);
                    })->whereHas('items'); // Ensure there are items
                } elseif ($repairStatus === 'unserviceable') {
                    // Filter for inspections where NOT ALL items have status = 1 (at least one item has status != 1)
                    $parachuteInspectionQuery = $parachuteInspectionQuery->whereHas('items', function ($query) {
                        $query->where('status', '!=', 1);
                    });
                }
            }

            // if (isset($searchKeyword)) {
            //     $parachuteInspectionQuery = $parachuteInspectionQuery->where('activity_name', 'LIKE', '%' . $searchKeyword . '%')->where('person_in_charge', 'LIKE', '%' . $searchKeyword . '%');
            // }
            if (isset($searchKeyword)) {
                $parachuteInspectionQuery = $parachuteInspectionQuery->where(function ($query) use ($searchKeyword) {
                    $searchableColumns = ['number', 'activity_name', 'person_in_charge'];
                    $searchTerm = strtolower($searchKeyword);

                    foreach ($searchableColumns as $column) {
                        $query->orWhereRaw("LOWER({$column}) LIKE ?", ['%' . $searchTerm . '%']);
                    }

                    $query->orWhereHas('parachute', function ($q) use ($searchTerm) {
                        $q->whereRaw("LOWER(serial_number) LIKE ?", ['%' . $searchTerm . '%'])
                            ->orWhereRaw("LOWER(part_number) LIKE ?", ['%' . $searchTerm . '%'])
                            ->orWhereRaw("LOWER(type) LIKE ?", ['%' . $searchTerm . '%'])
                            ->orWhereRaw("LOWER(category) LIKE ?", ['%' . $searchTerm . '%']);
                    });
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

    public function getOne($parachuteInspectionId)
    {
        try {
            $parachuteInspection = ParachuteInspection::with(['items.descriptions', 'parachute'])->find($parachuteInspectionId);
            return response()->json([
                'message' => 'OK',
                'data' => $parachuteInspection,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // return request()->all();
            // $file = $request->file('parachute_inspection_image_0');
            // if (!$file) {
            //     return response()->json([
            //         'message' => 'File tidak ditemukan',
            //     ], 400);
            // }

            // $request->validate([
            //     'date' => 'required|date',
            //     'amount' => 'required|numeric',
            //     'to' => 'nullable|max:255',
            //     'received_by' => 'nullable|max:255',
            //     'account_id' => 'nullable|exists:accounts_v2,id',
            //     'outlet_id' => 'nullable|exists:outlets,id',
            //     'created_by' => 'nullable|exists:users,id',
            // ]);

            $number = '';
            $date = Carbon::parse($request->date)->toDateString();

            $explodedNumber = explode('/', $request->number);
            // $iteratorPlaceholder = $explodedNumber[count($explodedNumber) - 1] ?? '';

            $dateParachuteInspectionCount = ParachuteInspection::withTrashed()->where('date', $date)->count();
            $number = 'PR' . '/' . Carbon::parse($date)->format('dmY') . '/' . sprintf('%03d', ($dateParachuteInspectionCount + 1));

            $parachuteInspectionWithNumber = ParachuteInspection::query()->where('number', $number)->first();
            if (isset($parachuteInspectionWithNumber)) {
                return response()->json([
                    'message' => 'Nomor sudah digunakan',
                ], 400);
            }

            // $totalAmount = collect($expenseItems)->map(function ($item) {
            //     return $item['amount'] ?? 0;
            // })->sum();

            $newParachuteInspection = new ParachuteInspection();
            $newParachuteInspection->number = $number;
            $newParachuteInspection->date = Carbon::parse($date)->toDateString();
            $newParachuteInspection->activity_name = $request->activity_name;
            $newParachuteInspection->person_in_charge = $request->person_in_charge;
            $newParachuteInspection->parachute_id = $request->parachute_id;
            $newParachuteInspection->created_by = $request->created_by;
            $newParachuteInspection->save();

            $parachuteInspectionItems = $request->items;

            collect($parachuteInspectionItems)->each(function ($item, $index) use ($newParachuteInspection, $request) {
                $parachuteInspectionId = $newParachuteInspection->id;

                // Photo
                $fileOriginalName = null;
                $filePath = null;
                $urlPath = null;
                $fileSize = null;
                if ($request->hasFile('parachute_inspection_image_' . $index)) {
                    $file = $request->file('parachute_inspection_image_' . $index);
                    // Uploading Image
                    $fileOriginalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $name = time() . '_parachute_inspection_' . $parachuteInspectionId . '_' . $index . '_' . $file->getClientOriginalName();
                    $filePath = $file->storePubliclyAs('images/parachute_inspections', $name, 'public');
                }

                $data = [
                    'parachute_inspection_id' => $parachuteInspectionId,
                    'description' => $item['description'] ?? null,
                    'image_url' => $filePath,
                    'image_file_name' => $fileOriginalName,
                    'image_file_size' => $fileSize,
                    'status' => $item['status'],
                    'status_date' => $item['status_date'],
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ];

                $parachuteInspectionItem = ParachuteInspectionItem::create($data);

                $descriptions = $item['descriptions'] ?? [];
                $descriptionsData = collect($descriptions)->map(function ($description) use ($parachuteInspectionItem) {
                    return [
                        'parachute_inspection_item_id' => $parachuteInspectionItem->id,
                        'description' => $description['text'] ?? null,
                        'type' => $description['type'] ?? null,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString(),
                    ];
                })->all();

                $parachuteInspectionItem->descriptions()->createMany($descriptionsData);
            });


            DB::commit();

            $savedParachuteInspection = ParachuteInspection::with(['items'])->find($newParachuteInspection->id);
            return response()->json([
                'message' => 'Data telah tersimpan',
                'data' => $savedParachuteInspection,
            ]);
            // ?----------------------------
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function update(Request $request, $parachuteInspectionId)
    {
        DB::beginTransaction();
        try {
            // return $request->all();
            // $file = $request->file('parachute_inspection_image_0');
            // if (!$file) {
            //     return response()->json([
            //         'message' => 'File tidak ditemukan',
            //     ], 400);
            // }

            // $request->validate([
            //     'date' => 'required|date',
            //     'amount' => 'required|numeric',
            //     'to' => 'nullable|max:255',
            //     'received_by' => 'nullable|max:255',
            //     'account_id' => 'nullable|exists:accounts_v2,id',
            //     'outlet_id' => 'nullable|exists:outlets,id',
            //     'created_by' => 'nullable|exists:users,id',
            // ]);

            $number = '';
            $date = Carbon::parse($request->date)->toDateString();

            $explodedNumber = explode('/', $request->number);
            // $iteratorPlaceholder = $explodedNumber[count($explodedNumber) - 1] ?? '';

            $dateParachuteInspectionCount = ParachuteInspection::withTrashed()->where('date', $date)->count();
            $number = 'PR' . '/' . Carbon::parse($date)->format('dmY') . '/' . sprintf('%03d', ($dateParachuteInspectionCount + 1));

            $parachuteInspectionWithNumber = ParachuteInspection::query()->where('number', $number)->first();
            if (isset($parachuteInspectionWithNumber)) {
                return response()->json([
                    'message' => 'Nomor sudah digunakan',
                ], 400);
            }

            // $totalAmount = collect($expenseItems)->map(function ($item) {
            //     return $item['amount'] ?? 0;
            // })->sum();

            $parachuteInspection = ParachuteInspection::find($parachuteInspectionId);
            // $parachuteInspection->number = $number;
            $parachuteInspection->date = Carbon::parse($date)->toDateString();
            $parachuteInspection->activity_name = $request->activity_name;
            $parachuteInspection->person_in_charge = $request->person_in_charge;
            $parachuteInspection->parachute_id = $request->parachute_id;
            $parachuteInspection->updated_by = $request->updated_by;
            $parachuteInspection->save();

            $parachuteInspectionItems = $request->items;
            $oldImages = json_decode($request->old_images, true);

            $parachuteInspection->items()->delete();

            collect($parachuteInspectionItems)->each(function ($item, $index) use ($parachuteInspection, $request, $oldImages) {
                $parachuteInspectionId = $parachuteInspection->id;

                // Photo
                $fileOriginalName = $oldImages[$index]['name'] ?? null;
                $filePath = $oldImages[$index]['url'] ?? null;
                $fileSize = $oldImages[$index]['size'] ?? null;
                if ($request->hasFile('parachute_inspection_image_' . $index)) {
                    $file = $request->file('parachute_inspection_image_' . $index);
                    // Uploading Image
                    $fileOriginalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $name = time() . '_parachute_inspection_' . $parachuteInspectionId . '_' . $index . '_' . $file->getClientOriginalName();
                    $filePath = $file->storePubliclyAs('images/parachute_inspections', $name, 'public');
                }

                $data = [
                    'parachute_inspection_id' => $parachuteInspectionId,
                    'description' => $item['description'] ?? null,
                    'image_url' => $filePath,
                    'image_file_name' => $fileOriginalName,
                    'image_file_size' => $fileSize,
                    'status' => $item['status'],
                    'status_date' => $item['status_date'],
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ];

                $parachuteInspectionItem = ParachuteInspectionItem::create($data);

                $descriptions = $item['descriptions'] ?? [];
                $descriptionsData = collect($descriptions)->map(function ($description) use ($parachuteInspectionItem) {
                    return [
                        'parachute_inspection_item_id' => $parachuteInspectionItem->id,
                        'description' => $description['text'] ?? null,
                        'type' => $description['type'] ?? null,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString(),
                    ];
                })->all();

                $parachuteInspectionItem->descriptions()->createMany($descriptionsData);
            });


            DB::commit();

            $savedParachuteInspection = ParachuteInspection::with(['items'])->find($parachuteInspection->id);
            return response()->json([
                'message' => 'Data telah tersimpan',
                'data' => $savedParachuteInspection,
                'request' => $request->all(),
            ]);
            // ?----------------------------
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function destroy($parachuteInspectionId)
    {
        DB::beginTransaction();
        try {
            $parachuteInspection = ParachuteInspection::find($parachuteInspectionId);

            if ($parachuteInspection == null) {
                throw new Error('Data pemeriksaan parasut tidak ditemukan');
            }

            $parachuteInspection->items()->delete();
            $parachuteInspection->delete();

            DB::commit();

            return response()->json([
                'message' => 'Data berhasil dihapus',
                'data' => $parachuteInspection,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}

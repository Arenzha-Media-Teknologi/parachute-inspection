<?php

namespace App\Http\Controllers;

use App\Models\Parachute;
use App\Models\ParachuteInspection;
use App\Models\ParachuteInspectionItem;
use App\Models\ParachuteInspectionItemDescription;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Yajra\DataTables\Facades\DataTables;
use Spatie\LaravelPdf\Facades\Pdf;

class ParachuteInspectionController extends Controller
{
    public function indexData(Request $request)
    {
        // $query = ParachuteInspection::with('parachute')->orderBy('id', 'desc');
        $query = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc');

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

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->filled('date_start')) {
            $query->where('date', '=', $request->date_start);
        }
        if ($request->filled('type')) {
            $query->whereHas('parachute', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->type . '%');
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('items', function ($q) use ($request) {
                if ($request->status === '1') {
                    // Serviceable
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    // Unserviceable
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')
                            ->orWhereNull('status');
                    });
                }
            });
        }

        $userIds = $query->pluck('updated_by')->merge($query->pluck('created_by'))->unique()->filter();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

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
            ->addColumn('user', function ($inspection) use ($users) {
                $user = $users->get($inspection->updated_by) ?? $users->get($inspection->created_by);
                return $user ? $user->name : '-';
            })

            ->addColumn('status', function ($inspection) {
                $items = $inspection->items ?? collect();
                if ($items->isEmpty()) {
                    return '<span class="text-danger">Unserviceable</span>';
                }
                $totalItems = $items->count();
                $serviceableCount = $items->filter(function ($item) {
                    return $item->status === '1';
                })->count();
                if ($serviceableCount === $totalItems) {
                    return '<span class="text-success">Serviceable</span>';
                } else {
                    return '<span class="text-danger">Unserviceable</span>';
                }
            })

            ->addColumn('action', 'web.layouts.button.parachute-inspection-button')
            ->rawColumns(['user', 'status', 'action'])
            ->make(true);
    }

    public function index()
    {
        // return view('web.parachute-inspection.index');
        $parachuteInspection = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc')->get();
        $statuses = $parachuteInspection->flatMap(function ($inspection) {
            return $inspection->items->pluck('status');
        });
        // return $statuses;

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
            // $parachute = ParachuteInspection::find($id);
            $parachute = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->find($id);
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

    public function edit(string $id)
    {
        $parachuteInspection = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->find($id);
        // return $parachuteInspection;
        $parachute = Parachute::orderBy('id', 'desc')->get();
        $newCode = $this->generateCode();

        return view('web.parachute-inspection.edit', [
            'parachute_inspection' => $parachuteInspection,
            'parachute' => $parachute,
            'new_code' => $newCode,
        ]);
    }

    // public function update_old(Request $request, string $id)
    // {
    //     // $user_id = Auth::user()->id;
    //     $validated = $request->validate([
    //         'date' => 'required|date',
    //         'activity' => 'nullable|string|max:255',
    //         'checker' => 'nullable|string|max:255',
    //     ]);
    //     // return $request->all();
    //     try {
    //         DB::beginTransaction();
    //         // $parachuteInspection = ParachuteInspection::with(['parachute', 'items'])->find($id);
    //         // $parachuteInspection->date = $validated['date'];
    //         // $parachuteInspection->activity_name = $validated['activity'];
    //         // $parachuteInspection->person_in_charge = $validated['checker'];
    //         // // $parachuteInspection->created_by = $user_id;
    //         // $parachuteInspection->save();
    //         $parachuteInspection = ParachuteInspection::with(['parachute', 'items'])->findOrFail($id);
    //         $parachuteInspection->update([
    //             'date' => $validated['date'],
    //             'activity_name' => $validated['activity'] ?? null,
    //             'person_in_charge' => $validated['checker'] ?? null,
    //         ]);
    //         $keptItemIds = [];
    //         if ($request->has('items') && is_array($request->items)) {
    //             $allUpatedData = [];
    //             foreach ($request->items as $index => $item) {
    //                 if (!empty($item['id'])) {
    //                     $existingItem = $parachuteInspection->items->firstWhere('id', $item['id']);
    //                     if ($existingItem) {
    //                         $updateData = [];
    //                         if ($existingItem->description !== ($item['description'] ?? '')) {
    //                             $updateData['description'] = $item['description'] ?? null;
    //                         }
    //                         if ($request->hasFile("items.$index.file")) {
    //                             Storage::disk('public')->delete($existingItem->image_url);
    //                             $file = $request->file("items.$index.file");
    //                             $filePath = $file->store('parachute-files', 'public');
    //                             $updateData['image_url'] = $filePath;
    //                             $updateData['image_file_name'] = $file->getClientOriginalName();
    //                             $updateData['image_file_size'] = $file->getSize();
    //                         }

    //                         $updateData['created_at'] = isset($item['created']) ? Carbon::parse($item['created'])->format('Y-m-d H:i:s') : now();
    //                         // $updateData['created_at'] = '2000-01-01 17:00:00';
    //                         $allUpatedData[] = $updateData;
    //                         if (!empty($updateData)) {
    //                             $existingItem->update($updateData);
    //                             // $parachuteItem = ParachuteInspectionItem::find($item['id']);
    //                             // $parachuteItem->created_at = ParachuteInspectionItem::find($item['id']);
    //                             // $allUpatedData[] = ['testtest'];
    //                         }

    //                         $keptItemIds[] = $existingItem->id;
    //                     }
    //                 } elseif (isset($item['file'])) {
    //                     $file = $request->file("items.$index.file");
    //                     $filePath = $file->store('parachute-files', 'public');
    //                     $newItem = $parachuteInspection->items()->create([
    //                         'description' => $item['description'] ?? null,
    //                         'image_url' => $filePath,
    //                         'image_file_name' => $file->getClientOriginalName(),
    //                         'image_file_size' => $file->getSize(),
    //                         'created_at' => isset($item['created']) ? Carbon::parse($item['created'])->format('Y-m-d H:i:s') : now(),
    //                     ]);
    //                     $keptItemIds[] = $newItem->id;
    //                 }
    //             }
    //             foreach ($parachuteInspection->items as $existingItem) {
    //                 if (!in_array($existingItem->id, $keptItemIds)) {
    //                     Storage::disk('public')->delete($existingItem->image_url);
    //                     $existingItem->delete();
    //                 }
    //             }
    //         }
    //         // return $allUpatedData;

    //         DB::commit();
    //         return response()->json([
    //             'message' => 'Data berhasil diperbaharui',
    //             'data' => $parachuteInspection->load('items'),
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Terjadi kesalahan saat memperbaharui data.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function update(Request $request, string $id)
    {
        // $user_id = Auth::user()->id;
        $validated = $request->validate([
            'date'    => 'required|date',
            'activity' => 'nullable|string|max:255',
            'checker' => 'nullable|string|max:255',
            'items'   => 'required|array',
        ]);
        DB::beginTransaction();
        try {
            $pi = ParachuteInspection::with('items.itemDescriptions')->findOrFail($id);
            $pi->update([
                'date'           => $validated['date'],
                'activity_name'  => $validated['activity'] ?? null,
                'person_in_charge' => $validated['checker'] ?? null,
            ]);

            $keptItemIds = [];
            $keptDescIds = [];
            foreach ($request->items as $idx => $item) {
                if (empty($item['parent_item_id']) && isset($item['created'])) {
                    if (!empty($item['id'])) {
                        $model = $pi->items()->find($item['id']);
                        if ($model) {
                            $update = [];
                            if ($model->description !== ($item['description'] ?? '')) {
                                $update['description'] = $item['description'];
                            }
                            if (isset($item['status'])) {
                                $update['status'] = ($item['status'] === true || $item['status'] === '1' || $item['status'] === 1 || $item['status'] === 'true') ? '1' : '0';
                                if (!empty($item['status_date'])) {
                                    $update['status_date'] = Carbon::parse($item['status_date'])->format('Y-m-d H:i:s');
                                }
                                // $update['status_date'] = isset($item['status_date']) && !empty($item['status_date'])
                                //     ? Carbon::createFromFormat('Y-m-d\TH:i', $item['status_date'])->format('Y-m-d H:i:s')
                                //     : null;
                                //     Carbon::parse($item['status_date'])->format('Y-m-d H:i:s');

                            }

                            if ($request->hasFile("items.$idx.file")) {
                                Storage::disk('public')->delete($model->image_url);
                                $f = $request->file("items.$idx.file");
                                $path = $f->store('parachute-files', 'public');
                                $update['image_url']       = $path;
                                $update['image_file_name'] = $f->getClientOriginalName();
                                $update['image_file_size'] = $f->getSize();
                            }
                            $update['created_at'] = Carbon::parse($item['created'])->format('Y-m-d H:i:s');
                            // $update['created_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $item['created'])->format('Y-m-d H:i:s');

                            if ($update) {
                                $model->update($update);
                            }
                        }
                    } else {
                        $data = [
                            'description' => $item['description'] ?? null,
                            'status' => ($item['status'] === true || $item['status'] === '1' || $item['status'] === 1 || $item['status'] === 'true') ? '1' : '0',
                            'created_at'  => Carbon::parse($item['created'])->format('Y-m-d H:i:s'),
                            // 'created_at'  => Carbon::createFromFormat('Y-m-d\TH:i', $item['created'])->format('Y-m-d H:i:s'),
                            'status_date' => Carbon::parse($item['status_date'])->format('Y-m-d H:i:s'),
                            // 'status_date' => isset($item['status_date']) && !empty($item['status_date'])
                            //     ? Carbon::createFromFormat('Y-m-d\TH:i', $item['status_date'])->format('Y-m-d H:i:s')
                            //     : null,

                        ];
                        if ($request->hasFile("items.$idx.file")) {
                            $f = $request->file("items.$idx.file");
                            $path = $f->store('parachute-files', 'public');
                            $data['image_url']       = $path;
                            $data['image_file_name'] = $f->getClientOriginalName();
                            $data['image_file_size'] = $f->getSize();
                        }
                        $model = $pi->items()->create($data);
                    }
                    if (!empty($model) && $model->id) {
                        $keptItemIds[] = $model->id;
                    }
                    continue;
                }

                if (!empty($item['parent_item_id']) && !empty($item['type'])) {
                    $parent = $pi->items()->find($item['parent_item_id']);
                    if (!$parent) {
                        continue;
                    }
                    if (!empty($item['id'])) {
                        $desc = $parent->itemDescriptions()->find($item['id']);
                        if ($desc) {
                            $ud = [];
                            if ($desc->type !== $item['type']) {
                                $ud['type'] = $item['type'];
                            }
                            if ($desc->description !== ($item['description'] ?? '')) {
                                $ud['description'] = $item['description'];
                            }
                            if ($ud) {
                                $desc->update($ud);
                            }
                            $keptDescIds[] = $desc->id;
                        }
                    } else {
                        $new = $parent->itemDescriptions()->create([
                            'type'        => $item['type'],
                            'description' => $item['description'] ?? null,
                        ]);
                        if ($new && $new->id) {
                            $keptDescIds[] = $new->id;
                        }
                    }
                }
            }

            $pi->items()->whereNotIn('id', $keptItemIds)->get()
                ->each(function ($it) {
                    Storage::disk('public')->delete($it->image_url);
                    $it->delete();
                });

            ParachuteInspectionItemDescription::whereNotIn('id', $keptDescIds)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Data berhasil diperbaharui',
                'data'   => $pi->load('items.itemDescriptions'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbaharui data.',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }


    public function reportPdf_old(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
        ]);
        // $query = ParachuteInspection::with('parachute')->orderBy('id', 'desc');
        $query = ParachuteInspection::with(['parachute', 'items'])->orderBy('id', 'desc');

        if (!empty($request->number)) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', '%' . $request->number . '%')
                    ->orWhereHas('parachute', function ($p) use ($request) {
                        $p->where('type', 'like', '%' . $request->number . '%')
                            ->orWhere('part_number', 'like', '%' . $request->number . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->number . '%');
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
            $query->where('date', '=', $request->date_start);
        }

        $data = $query->get();
        // return [
        //     'data' => $data,
        //     'date_start' => $request->date_start,
        //     'date_end' => $request->date_end,
        // ];

        return Pdf::view('web.parachute-inspection.report', [
            'data' => $data,
            'type' => $request->type,
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
        ])
            ->format('A4')
            ->name('Laporan Inspeksi Parasut.pdf')
            ->download();
    }

    public function reportPreview(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            // 'bulan_romawi' => 'required|string',
            // 'tahun' => 'required|string',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
        ]);
        // $query = ParachuteInspection::with('parachute')->orderBy('id', 'desc');
        $query = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc');

        if (!empty($request->number)) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', '%' . $request->number . '%')
                    ->orWhereHas('parachute', function ($p) use ($request) {
                        $p->where('type', 'like', '%' . $request->number . '%')
                            ->orWhere('part_number', 'like', '%' . $request->number . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->number . '%');
                    });
            });
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->filled('date_start')) {
            $query->where('date', '=', $request->date_start);
        }
        if ($request->filled('type')) {
            $query->whereHas('parachute', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->type . '%');
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('items', function ($q) use ($request) {
                if ($request->status === '1') {
                    // Serviceable
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    // Unserviceable
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')
                            ->orWhereNull('status');
                    });
                }
            });
        }
        $results  = $query->get();
        $data = [
            'title' => 'Laporan Pemeriksaan',
            'date' => now()->format('d-m-Y'),
            'data' => $results,
            'date_start' => $request->date_start,
            'periode' => $request->periode,
            // 'bulan_romawi' => $request->bulan_romawi,
            // 'tahun' => $request->tahun,
            'date_end' => $request->date_end,
            'type' => $request->type,
        ];

        return view('web.parachute-inspection.report-preview', $data);
    }

    public function reportPdf(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            // 'bulan_romawi' => 'required|string',
            // 'tahun' => 'required|string',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
        ]);

        $query = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc');

        if (!empty($request->number)) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', '%' . $request->number . '%')
                    ->orWhereHas('parachute', function ($p) use ($request) {
                        $p->where('type', 'like', '%' . $request->number . '%')
                            ->orWhere('part_number', 'like', '%' . $request->number . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->number . '%');
                    });
            });
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->filled('date_start')) {
            $query->where('date', '=', $request->date_start);
        }
        if ($request->filled('type')) {
            $query->whereHas('parachute', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->type . '%');
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('items', function ($q) use ($request) {
                if ($request->status === '1') {
                    // Serviceable
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    // Unserviceable
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')
                            ->orWhereNull('status');
                    });
                }
            });
        }
        $data = $query->get();

        $htmlContent = view('web.parachute-inspection.report', [
            'title' => 'Laporan Pemeriksaan Parasut',
            'date' => now()->format('d-m-Y'),
            'data' => $data,
            'date_start' => $request->date_start,
            'periode' => $request->periode,
            // 'bulan_romawi' => $request->bulan_romawi,
            // 'tahun' => $request->tahun,
            'date_end' => $request->date_end,
            'type' => $request->type,
        ])->render();

        $fileName = 'report_' . time() . '.pdf';
        $pdfPath = storage_path('app/public/' . $fileName);

        Browsershot::html($htmlContent)
            ->format('A4')
            ->showBackground()
            ->save($pdfPath);

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $fileName),
        ]);
    }

    public function reportAttachmentPreview(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            // 'bulan_romawi' => 'required|string',
            // 'tahun' => 'required|string',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
        ]);
        // $query = ParachuteInspection::with('parachute')->orderBy('id', 'desc');
        $query = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc');

        if (!empty($request->number)) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', '%' . $request->number . '%')
                    ->orWhereHas('parachute', function ($p) use ($request) {
                        $p->where('type', 'like', '%' . $request->number . '%')
                            ->orWhere('part_number', 'like', '%' . $request->number . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->number . '%');
                    });
            });
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->filled('date_start')) {
            $query->where('date', '=', $request->date_start);
        }
        if ($request->filled('type')) {
            $query->whereHas('parachute', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->type . '%');
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('items', function ($q) use ($request) {
                if ($request->status === '1') {
                    // Serviceable
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    // Unserviceable
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')
                            ->orWhereNull('status');
                    });
                }
            });
        }
        $results  = $query->get();
        // return $results;
        $data = [
            'title' => 'Lampiran Pemeriksaan Parasut',
            'date' => now()->format('d-m-Y'),
            'data' => $results->toArray(),
            'date_start' => $request->date_start,
            'periode' => $request->periode,
            // 'bulan_romawi' => $request->bulan_romawi,
            // 'tahun' => $request->tahun,
            'date_end' => $request->date_end,
            'type' => $request->type,
        ];

        return view('web.parachute-inspection.report-attachment-preview', $data);
    }
    public function reportAttachmentPdf(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            // 'bulan_romawi' => 'required|string',
            // 'tahun' => 'required|string',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
        ]);

        $query = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc');

        if (!empty($request->number)) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', '%' . $request->number . '%')
                    ->orWhereHas('parachute', function ($p) use ($request) {
                        $p->where('type', 'like', '%' . $request->number . '%')
                            ->orWhere('part_number', 'like', '%' . $request->number . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->number . '%');
                    });
            });
        }


        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        } elseif ($request->filled('date_start')) {
            $query->where('date', '=', $request->date_start);
        }
        if ($request->filled('type')) {
            $query->whereHas('parachute', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->type . '%');
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('items', function ($q) use ($request) {
                if ($request->status === '1') {
                    // Serviceable
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    // Unserviceable
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')
                            ->orWhereNull('status');
                    });
                }
            });
        }
        $data = $query->get();

        $htmlContent = view('web.parachute-inspection.report-attachment', [
            'title' => 'Lampiran Pemeriksaan Parasut',
            'date' => now()->format('d-m-Y'),
            'data' => $data->toArray(),
            'date_start' => $request->date_start,
            'periode' => $request->periode,
            // 'bulan_romawi' => $request->bulan_romawi,
            // 'tahun' => $request->tahun,
            'date_end' => $request->date_end,
            'type' => $request->type,
        ])->render();

        $fileName = 'report_' . time() . '.pdf';
        $pdfPath = storage_path('app/public/' . $fileName);

        try {
            Browsershot::html($htmlContent)
                ->format('A4')
                ->showBackground()
                ->timeout(300)
                ->save($pdfPath);

            return response()->json([
                'success' => true,
                'url' => asset('storage/' . $fileName),
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat PDF. Cek log untuk detail.',
            ], 500);
        }
    }
}

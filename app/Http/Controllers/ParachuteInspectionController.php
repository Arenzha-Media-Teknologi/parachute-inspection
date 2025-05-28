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
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Browsershot\Browsershot;
use Yajra\DataTables\Facades\DataTables;
use Spatie\LaravelPdf\Facades\Pdf;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $user_id = Auth::id();
        $validated = $request->validate([
            'code' => 'required|unique:parachute_inspections,number',
            'date' => 'required|date',
            'activity' => 'nullable|string|max:255',
            'checker' => 'nullable|string|max:255',
            'parachute_id' => 'required|exists:parachutes,id',
            'items' => 'nullable|array',
            'items.*.status_date' => 'nullable|date',
            'items.*.created' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $inspection = ParachuteInspection::create([
                'number' => $validated['code'],
                'date' => $validated['date'],
                'activity_name' => $validated['activity'],
                'person_in_charge' => $validated['checker'],
                'parachute_id' => $validated['parachute_id'],
                'created_by' => $user_id,
            ]);

            // Temp ID → Model mapping
            $tempItemMap = [];

            foreach ($request->items as $idx => $item) {
                // Jika item utama (bukan child)
                if (empty($item['parent_temp_id']) && isset($item['created'])) {
                    $data = [
                        'description' => $item['description'] ?? null,
                        'status' => ($item['status'] === true || $item['status'] === '1' || $item['status'] === 1 || $item['status'] === 'true') ? '1' : '0',
                        'created_at' => Carbon::parse($item['created'])->format('Y-m-d H:i:s'),
                    ];

                    if (!empty($item['status_date'])) {
                        $data['status_date'] = Carbon::parse($item['status_date'])->format('Y-m-d H:i:s');
                    }

                    if ($request->hasFile("items.$idx.file")) {
                        $f = $request->file("items.$idx.file");
                        $path = $f->store('parachute-files', 'public');
                        $data['image_url'] = $path;
                        $data['image_file_name'] = $f->getClientOriginalName();
                        $data['image_file_size'] = $f->getSize();
                    }

                    $model = $inspection->items()->create($data);

                    // Simpan ke map jika ada temp_id
                    if (!empty($item['temp_id'])) {
                        $tempItemMap[$item['temp_id']] = $model;
                    }

                    continue;
                }

                // Jika item deskripsi anak (utama/cadangan)
                if (!empty($item['description']) && !empty($item['parent_temp_id'])) {
                    $parentTempId = $item['parent_temp_id'];
                    if (isset($tempItemMap[$parentTempId])) {
                        $tempItemMap[$parentTempId]->itemDescriptions()->create([
                            'type' => $item['type'] ?? null,
                            'description' => $item['description'],
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
                    if (!empty($item['id']) && !empty($item['description'])) {
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
                        if (!empty($item['description'])) {
                            $new = $parent->itemDescriptions()->create([
                                'type'        => $item['type'],
                                'description' => $item['description'],
                            ]);
                            if ($new && $new->id) {
                                $keptDescIds[] = $new->id;
                            }
                        }
                    }
                }
            }

            $pi->items()->whereNotIn('id', $keptItemIds)->get()
                ->each(function ($it) {
                    Storage::disk('public')->delete($it->image_url);
                    $it->delete();
                });

            // ParachuteInspectionItemDescription::whereNotIn('id', $keptDescIds)->delete();
            $pi->items->each(function ($item) use ($keptDescIds) {
                $item->itemDescriptions()->whereNotIn('id', $keptDescIds)->delete();
            });

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
            'status' => 'nullable|string',
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
                    // $status = 'Serviceable';
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    // $status = 'Unserviceable';
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')
                            ->orWhereNull('status');
                    });
                }
            });
        }

        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
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
            'status' => $status ?? '',
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
            'status' => 'nullable|string',
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

        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
        }
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
            'status' => $status,
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
            'status' => 'nullable|string',
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
        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
        }
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
            'status' => $status,
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
            'status' => 'nullable|string',
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

        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
        }
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
            'status' => $status,
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

    public function reportWord(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
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
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')->orWhereNull('status');
                    });
                }
            });
        }
        $data = $query->get();

        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
        }

        // Buat file Word
        $phpWord = new PhpWord();
        // dalam twips (1 cm ≈ 567 twips)
        $sectionStyle = [
            'marginTop' => 567,
            'marginBottom' => 567,
            'marginLeft' => 567,
            'marginRight' => 567,
        ];
        $section = $phpWord->addSection($sectionStyle);

        // Header
        $table = $section->addTable(['alignment' => 'center']);
        $row = $table->addRow();
        // Kolom kiri
        $leftCell = $row->addCell(3500);
        $leftCell->addText('DEPO PEMELIHARAAN 70', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $leftCell->addText('SATUAN PEMELIHARAAN 72', ['bold' => true, 'size' => 10], [
            'alignment' => 'center',
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        // $leftCell->addShape('line', ['width' => 100, 'height' => 0, 'lineColor' => '000000']);
        // Spacer cell tengah
        $row->addCell(2000); // kosong di tengah
        // Kolom kanan
        $rightCell = $row->addCell(4000);
        $rightCell->addText('Lampiran Nota Dinas Dansathar 72', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $date = \Carbon\Carbon::parse($request->periode);
        $year = $date->format('Y');
        $month = (int) $date->format('m');
        $romawiBulan = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulan_romawi = $romawiBulan[$month];
        $rightCell->addText("Nomor B/ND-     /{$bulan_romawi}/{$year}/Sathar 72", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        \Carbon\Carbon::setLocale('id');
        $rightCell->addText('Tanggal        ' . $date->translatedFormat('F Y'), ['bold' => true, 'size' => 10], [
            'alignment' => 'center',
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        // $rightCell->addShape('line', ['width' => 100, 'height' => 0, 'lineColor' => '000000']);

        // Body
        $section->addTextBreak(3);
        $section->addText('LAPORAN PEMERIKSAAN PARASUT', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addTextBreak(2);
        $textRun = $section->addTextRun();
        $textRun->addText('Tanggal Pemeriksaan : ');
        $textRun->addText($request->date_start, ['bold' => true]);
        if ($request->date_end) {
            $textRun->addText(' s/d ');
            $textRun->addText($request->date_end, ['bold' => true]);
        }
        $section->addTextBreak(1);

        // Tambahkan tabel
        $fontHeader = [
            // 'name' => 'Calibri',
            'size' => 9, 'bold' => true
        ];
        $fontData = ['size' => 8];
        $alignCenter = ['alignment' => 'center'];
        // $bgColor = 'cccccc'; // wana abu muda
        $bgColor = 'ffffff';

        $tableStyle = [
            'size' => 9,
            'borderSize' => 6,
            'alignment' => 'center',
            'borderColor' => '000000',
        ];
        $phpWord->addTableStyle('InspectionTable', $tableStyle);
        $table = $section->addTable('InspectionTable');
        // === Baris Header 1 ===
        $table->addRow();
        $table->addCell(null, ['vMerge' => 'restart', 'valign' => 'center', 'bgColor' => $bgColor])
            ->addText('No.', $fontHeader, $alignCenter);
        $table->addCell(null, ['vMerge' => 'restart', 'valign' => 'center', 'bgColor' => $bgColor])
            ->addText('Kode Pemeriksaan', $fontHeader, $alignCenter);
        $table->addCell(null, ['vMerge' => 'restart', 'valign' => 'center', 'bgColor' => $bgColor])
            ->addText('Part Number', $fontHeader, $alignCenter);
        $table->addCell(null, ['gridSpan' => 3, 'valign' => 'center', 'bgColor' => $bgColor])
            ->addText('Serial Number', $fontHeader, $alignCenter);
        $table->addCell(null, ['vMerge' => 'restart', 'valign' => 'center', 'bgColor' => $bgColor])
            ->addText('Keterangan', $fontHeader, $alignCenter);

        // === Baris Header 2 ===
        $table->addRow();
        $table->addCell(null, ['vMerge' => 'continue', 'bgColor' => $bgColor]);
        $table->addCell(null, ['vMerge' => 'continue', 'bgColor' => $bgColor]);
        $table->addCell(null, ['vMerge' => 'continue', 'bgColor' => $bgColor]);
        $table->addCell(null, ['bgColor' => $bgColor])->addText('Bag', $fontHeader, $alignCenter);
        $table->addCell(null, ['bgColor' => $bgColor])->addText('Parasut Utama', $fontHeader, $alignCenter);
        $table->addCell(null, ['bgColor' => $bgColor])->addText('Parasut Cadangan', $fontHeader, $alignCenter);
        $table->addCell(null, ['vMerge' => 'continue', 'bgColor' => $bgColor]);

        // === Isi Tabel (Data) ===
        foreach ($data as $index => $item) {
            $table->addRow();
            $parachute = $item->parachute;
            $table->addCell(null)->addText($index + 1, $fontData, $alignCenter);
            $table->addCell(null)->addText($item->number ?? '-', $fontData, $alignCenter);
            $table->addCell(null)->addText($parachute->part_number ?? '-', $fontData, $alignCenter);
            // Serial Number (3 kolom)
            for ($i = 0; $i < 3; $i++) {
                $table->addCell(null)->addText($parachute->serial_number ?? '-', $fontData, $alignCenter);
            }
            // Keterangan
            $descriptionCell = $table->addCell(null);
            foreach ($item->items as $subitem) {
                $utama = $subitem->itemDescriptions->where('type', 'utama');
                $cadangan = $subitem->itemDescriptions->where('type', 'cadangan');
                if ($utama->isNotEmpty()) {
                    $descriptionCell->addText('Utama:', ['bold' => true, 'size' => 8]);
                    foreach ($utama as $desc) {
                        $descriptionCell->addText('- ' . $desc->description, $fontData);
                    }
                }
                if ($cadangan->isNotEmpty()) {
                    $descriptionCell->addText('Cadangan:', ['bold' => true, 'size' => 8]);
                    foreach ($cadangan as $desc) {
                        $descriptionCell->addText('- ' . $desc->description, $fontData);
                    }
                }
            }
        }
        // tanda tangan
        $section->addTextBreak(2);
        $signatureTable = $section->addTable([
            'alignment' => 'center',
            'width' => 100 * 50, // 100% lebar halaman (optional)
        ]);
        $signatureTable->addRow();
        // Kiri
        $leftCell = $signatureTable->addCell(4500);
        $leftCell->addText('Mengetahui,', ['size' => 10]);
        $leftCell->addText('Dansathar 72', ['size' => 10]);
        $leftCell->addTextBreak(4); // Spasi untuk tanda tangan
        $leftCell->addText('[NAMA PEJABAT]', ['underline' => 'single', 'bold' => true]);
        $leftCell->addText('[Pangkat, NRP]', ['size' => 10]);
        // Kanan
        $rightCell = $signatureTable->addCell(4500);
        $rightCell->addText('Yang Membuat,', ['size' => 10]);
        $rightCell->addText('Petugas Pemeriksa', ['size' => 10]);
        $rightCell->addTextBreak(4); // Spasi untuk tanda tangan
        $rightCell->addText('[NAMA PETUGAS]', ['underline' => 'single', 'bold' => true]);
        $rightCell->addText('[Pangkat, NRP]', ['size' => 10]);

        $fileName = 'report_' . time() . '.docx';
        $filePath = storage_path('app/public/' . $fileName);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $fileName),
        ]);
    }

    public function reportExcel(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
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
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')->orWhereNull('status');
                    });
                }
            });
        }
        $data = $query->get();

        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
        }

        $periode = $request->periode;
        $date = \Carbon\Carbon::parse($periode);
        $year = $date->format('Y');
        $month = (int)$date->format('m');
        $romawiBulan = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulan_romawi = $romawiBulan[$month];
        \Carbon\Carbon::setLocale('id');
        $tanggalText = 'Tanggal        ' . $date->translatedFormat('F Y');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'DEPO PEMELIHARAAN 70');
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'SATUAN PEMELIHARAAN 72');
        // $sheet->mergeCells('F1:G1');
        $sheet->setCellValue('G1', 'Lampiran Nota Dinas Dansathar 72');
        $sheet->setCellValue('G2', "Nomor B/ND-     /$bulan_romawi/$year/Sathar 72");
        $sheet->setCellValue('G3', $tanggalText);
        foreach (['A1', 'A2', 'G1', 'G2', 'G3'] as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getStyle('A2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
        $sheet->getStyle('B2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
        $sheet->getStyle('C2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
        $sheet->getStyle('G3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);

        $sheet->mergeCells('A7:G7');
        $sheet->setCellValue('A7', 'LAPORAN PEMERIKSAAN PARASUT');
        $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A9:G9');

        $richText = new RichText();
        $richText->createText('Tanggal Pemeriksaan : ');
        $tanggalAwal = \Carbon\Carbon::parse($request->date_start)->format('d-m-Y');
        $tanggalAkhir = $request->date_end ? \Carbon\Carbon::parse($request->date_end)->format('d-m-Y') : null;
        $boldStart = $richText->createTextRun($tanggalAwal);
        $boldStart->getFont()->setBold(true);
        if ($tanggalAkhir) {
            $richText->createText(' s/d ');
            $boldEnd = $richText->createTextRun($tanggalAkhir);
            $boldEnd->getFont()->setBold(true);
        }
        $sheet->setCellValue('A9', $richText);
        $sheet->getStyle('A9')->getFont()->setSize(10);
        $sheet->getStyle('A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Kolom
        $sheet->setCellValue('A11', 'No.');
        $sheet->setCellValue('B11', 'Kode Pemeriksaan');
        $sheet->setCellValue('C11', 'Part Number');
        $sheet->setCellValue('D11', 'Serial Number');
        $sheet->setCellValue('D12', 'Bag');
        $sheet->setCellValue('E12', 'Parasut Utama');
        $sheet->setCellValue('F12', 'Parasut Cadangan');
        $sheet->setCellValue('G11', 'Keterangan');

        // Merge cells untuk header dua baris sesuai desain Word
        $sheet->mergeCells('A11:A12'); // No.
        $sheet->mergeCells('B11:B12'); // Kode Pemeriksaan
        $sheet->mergeCells('C11:C12'); // Part Number
        $sheet->mergeCells('D11:F11'); // Serial Number
        $sheet->mergeCells('G11:G12'); // Keterangan

        // Styling header tabel
        $sheet->getStyle('A11:G12')->getFont()->setBold(true);
        $sheet->getStyle('A11:G12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A11:G12')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A11:G12')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('E12')->getAlignment()->setWrapText(true);
        $sheet->getStyle('F12')->getAlignment()->setWrapText(true);

        // Set column widths supaya rapi
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(50);
        $sheet->getRowDimension(12)->setRowHeight(40);

        // Data
        $row = 13;
        $no = 1;
        foreach ($data as $item) {
            $parachute = $item->parachute;
            // Ambil deskripsi utama dan cadangan gabung jadi satu string multiline
            $descLines = [];
            foreach ($item->items as $subitem) {
                $utama = $subitem->itemDescriptions->where('type', 'utama');
                $cadangan = $subitem->itemDescriptions->where('type', 'cadangan');

                if ($utama->isNotEmpty()) {
                    $descLines[] = 'Utama:';
                    foreach ($utama as $desc) {
                        $descLines[] = '- ' . $desc->description;
                    }
                }
                if ($cadangan->isNotEmpty()) {
                    $descLines[] = 'Cadangan:';
                    foreach ($cadangan as $desc) {
                        $descLines[] = '- ' . $desc->description;
                    }
                }
            }
            $descText = implode("\n", $descLines);
            $sheet->getStyle("A{$row}:G{$row}")->getFont()->setSize(10);
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $item->number ?? '-');
            $sheet->setCellValue("C{$row}", $parachute->part_number ?? '-');
            $sheet->setCellValue("D{$row}", $parachute->serial_number ?? '-');
            $sheet->setCellValue("E{$row}", $parachute->serial_number ?? '-');
            $sheet->setCellValue("F{$row}", $parachute->serial_number ?? '-');
            $sheet->setCellValue("G{$row}", $descText);
            // Styling data tabel
            $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("F{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            // Wrap text untuk kolom Keterangan
            $sheet->getStyle("G{$row}")->getAlignment()->setWrapText(true);
            // Border tiap baris data
            $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }
        $currentRow = $row + 3;

        // Kolom tanda tangan dibagi menjadi dua bagian, kita merge masing-masing 3 kolom (total 6 kolom dari 7)
        $sheet->mergeCells("B{$currentRow}:D{$currentRow}");
        $sheet->setCellValue("B{$currentRow}", 'Mengetahui,');
        // $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
        $sheet->setCellValue("G{$currentRow}", 'Yang Membuat,');
        $currentRow++;

        $sheet->mergeCells("B{$currentRow}:D{$currentRow}");
        $sheet->setCellValue("B{$currentRow}", 'Dansathar 72');
        // $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
        $sheet->setCellValue("G{$currentRow}", 'Petugas Pemeriksa');
        // Spasi untuk tanda tangan
        $currentRow += 4;

        $sheet->mergeCells("B{$currentRow}:D{$currentRow}");
        $sheet->setCellValue("B{$currentRow}", '[NAMA PEJABAT]');
        $sheet->getStyle("B{$currentRow}")->getFont()->setUnderline(true)->setBold(true);

        // $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
        $sheet->setCellValue("G{$currentRow}", '[NAMA PETUGAS]');
        $sheet->getStyle("G{$currentRow}")->getFont()->setUnderline(true)->setBold(true);
        $currentRow++;

        $sheet->mergeCells("B{$currentRow}:D{$currentRow}");
        $sheet->setCellValue("B{$currentRow}", '[Pangkat, NRP]');
        // $sheet->mergeCells("E{$currentRow}:G{$currentRow}");
        $sheet->setCellValue("G{$currentRow}", '[Pangkat, NRP]');

        // Rata tengah semua kolom tanda tangan
        for ($row = $currentRow - 6; $row <= $currentRow; $row++) {
            $sheet->getStyle("B{$row}:G{$row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $filename = "laporan_pemeriksaan_parasut_{$periode}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }


    public function reportAttachmentWord(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'periode' => 'required|date',
            'date_end' => 'nullable|date',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
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
                    $q->where('status', '1');
                } elseif ($request->status === '0') {
                    $q->where(function ($sub) {
                        $sub->where('status', '!=', '1')->orWhereNull('status');
                    });
                }
            });
        }
        $data = $query->get();

        if ($request->status === '1') {
            $status = 'Serviceable';
        } elseif ($request->status === '0') {
            $status = 'Unserviceable';
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        // dalam twips (1 cm ≈ 567 twips)
        $sectionStyle = [
            'marginTop' => 567,
            'marginBottom' => 567,
            'marginLeft' => 567,
            'marginRight' => 567,
        ];
        $section = $phpWord->addSection($sectionStyle);

        // Header
        $table = $section->addTable(['alignment' => 'center']);
        $row = $table->addRow();
        // Kolom kiri
        $leftCell = $row->addCell(3500);
        $leftCell->addText('DEPO PEMELIHARAAN 70', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $leftCell->addText('SATUAN PEMELIHARAAN 72', ['bold' => true, 'size' => 10], [
            'alignment' => 'center',
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        // $leftCell->addShape('line', ['width' => 100, 'height' => 0, 'lineColor' => '000000']);
        // Spacer cell tengah
        $row->addCell(2000); // kosong di tengah
        // Kolom kanan
        $rightCell = $row->addCell(4000);
        $rightCell->addText('Lampiran Nota Dinas Dansathar 72', ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $date = \Carbon\Carbon::parse($request->periode);
        $year = $date->format('Y');
        $month = (int) $date->format('m');
        $romawiBulan = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulan_romawi = $romawiBulan[$month];
        $rightCell->addText("Nomor B/ND-     /{$bulan_romawi}/{$year}/Sathar 72", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        \Carbon\Carbon::setLocale('id');
        $rightCell->addText('Tanggal        ' . $date->translatedFormat('F Y'), ['bold' => true, 'size' => 10], [
            'alignment' => 'center',
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        // $rightCell->addShape('line', ['width' => 100, 'height' => 0, 'lineColor' => '000000']);

        // Body
        $section->addTextBreak(3);
        $section->addText('DOKUMENTASI KERUSAKAN PARASUT', ['bold' => true, 'size' => 14, 'underline' => 'single'], [
            'alignment' => 'center',
            // 'borderBottomSize' => 6,
            // 'borderBottomColor' => '000000',
        ]);
        $section->addTextBreak(2);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 50,
        ];
        $phpWord->addTableStyle('inspectionTable', $tableStyle);
        $table = $section->addTable('inspectionTable');

        foreach ($data as $index => $item) {
            $table->addRow();
            $table->addCell(800)->addText(($index + 1) . '.', ['bold' => true]);

            $pn = $item['parachute']['part_number'] ?? '-';
            $sn = $item['parachute']['serial_number'] ?? '-';
            $table->addCell(8000, ['gridSpan' => 2])->addText("PN: $pn     SN: $sn", ['bold' => true]);

            foreach ($item['items'] as $subitem) {
                $table->addRow();

                // Cell Gambar utama item
                $cell1 = $table->addCell(4000, ['valign' => 'top']);
                $imageUrl = $subitem['image_url'] ?? null;
                if ($imageUrl) {
                    $imagePath = storage_path('app/public/' . $imageUrl);
                    $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                    $supportedExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                    if (file_exists($imagePath) && in_array($ext, $supportedExts)) {
                        try {
                            $cell1->addImage($imagePath, [
                                'width' => 175,
                                'height' => 100,
                            ]);
                        } catch (Exception $e) {
                            $cell1->addText('– Gagal memuat gambar –');
                        }
                    } elseif (!file_exists($imagePath)) {
                        $cell1->addText('– Gambar tidak ditemukan –');
                    } else {
                        $cell1->addText("– Format gambar .$ext tidak didukung –");
                    }
                } else {
                    $cell1->addText('– Gambar kosong –');
                }
                // Cell Deskripsi + gambar dari pivot
                $cell2 = $table->addCell(6000, ['valign' => 'top']);

                $descs = $subitem->itemDescriptions ?? collect();
                $utama = $descs->filter(fn ($d) => strtolower($d->type ?? '') === 'utama');
                $cadangan = $descs->filter(fn ($d) => strtolower($d->type ?? '') === 'cadangan');

                if ($utama->count()) {
                    $cell2->addText('Utama:', ['bold' => true]);
                    foreach ($utama as $d) {
                        $cell2->addText('• ' . ($d->description ?? '-'));
                        $pivotImage = $d->pivot->image_url ?? null;
                        if ($pivotImage) {
                            $imgPath = storage_path('app/public/' . $pivotImage);
                            if (file_exists($imgPath)) {
                                try {
                                    $cell2->addImage($imgPath, [
                                        'width' => 150,
                                        'height' => 80,
                                        'wrappingStyle' => 'square',
                                        'alignment' => 'left',
                                    ]);
                                } catch (\Exception $e) {
                                    $cell2->addText('   (gagal memuat gambar)');
                                }
                            } else {
                                $cell2->addText('   (gambar tidak ditemukan)');
                            }
                        }
                    }
                }
                if ($cadangan->count()) {
                    $cell2->addText('Cadangan:', ['bold' => true]);
                    foreach ($cadangan as $d) {
                        $cell2->addText('• ' . ($d->description ?? '-'));
                        $pivotImage = $d->pivot->image_url ?? null;
                        if ($pivotImage) {
                            $imgPath = storage_path('app/public/' . $pivotImage);
                            if (file_exists($imgPath)) {
                                try {
                                    $cell2->addImage($imgPath, [
                                        'width' => 150,
                                        'height' => 80,
                                        'wrappingStyle' => 'square',
                                        'alignment' => 'left',
                                    ]);
                                } catch (\Exception $e) {
                                    $cell2->addText('   (gagal memuat gambar)');
                                }
                            } else {
                                $cell2->addText('   (gambar tidak ditemukan)');
                            }
                        }
                    }
                }
                if (!count($utama) && !count($cadangan)) {
                    $cell2->addText('– Tidak ada deskripsi –');
                }
            }
        }
        // tanda tangan
        $section->addTextBreak(2);
        $signatureTable = $section->addTable([
            'alignment' => 'center',
            'width' => 100 * 50, // 100% lebar halaman (optional)
        ]);
        $signatureTable->addRow();
        // Kiri
        $leftCell = $signatureTable->addCell(4500);
        $leftCell->addText('Mengetahui,', ['size' => 10]);
        $leftCell->addText('Dansathar 72', ['size' => 10]);
        $leftCell->addTextBreak(4); // Spasi untuk tanda tangan
        $leftCell->addText('[NAMA PEJABAT]', ['underline' => 'single', 'bold' => true]);
        $leftCell->addText('[Pangkat, NRP]', ['size' => 10]);
        // Kanan
        $rightCell = $signatureTable->addCell(4500);
        $rightCell->addText('Yang Membuat,', ['size' => 10]);
        $rightCell->addText('Petugas Pemeriksa', ['size' => 10]);
        $rightCell->addTextBreak(4); // Spasi untuk tanda tangan
        $rightCell->addText('[NAMA PETUGAS]', ['underline' => 'single', 'bold' => true]);
        $rightCell->addText('[Pangkat, NRP]', ['size' => 10]);

        $filename = 'inspection-report-' . \Illuminate\Support\Str::uuid() . '.docx';
        $tempPath = storage_path("app/temp/$filename");
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0775, true);
        }
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, 'inspection-report.docx')->deleteFileAfterSend(true);
    }


    public function reportUnserviceable(Request $request)
    {
        $query = ParachuteInspection::with(['parachute', 'items.itemDescriptions'])->orderBy('id', 'desc');
        $query->whereHas('items', function ($q) use ($request) {
            $q->where(function ($sub) {
                $sub->where('status', '!=', '1')
                    ->orWhereNull('status');
            });
        });
        $results  = $query->get();
        $data = [
            'title' => 'UNSERVICEABLE TAG',
            'date' => now()->format('d-m-Y'),
            'data' => $results,
        ];

        return view('web.parachute-inspection.report-unserviceable', $data);
    }
}

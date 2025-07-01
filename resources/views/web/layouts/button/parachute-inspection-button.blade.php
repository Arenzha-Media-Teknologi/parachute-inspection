@php
$permission = json_decode(Auth::user()->user_groups->permissions);
$isUnserviceable = collect($model->items)->contains(function ($item) {
return $item->status !== '1' || is_null($item->status);
});
@endphp

<!-- <div class="text-center"> -->
<div class="d-flex align-items-center ">
    @if(in_array("edit_parachute_check", $permission))
    <!-- <a href="#" class="btn btn-sm btn-warning hover-scale btn-detail me-2" data-id="{{ $model->id }}" data-bs-toggle="modal" data-bs-target="#kt_modal_detail"> -->
    <a href="/parachute-inspection/edit/{{ $model->id }}" class="btn btn-sm btn-warning hover-scale me-2" style="background-color: #607D8B" data-id="{{ $model->id }}">
        <i class="fas fa-file-alt fs-4 me-2"></i> Riwayat
    </a>
    @endif

    @if(in_array("delete_parachute_check", $permission))
    <a href="#" data-id="{{ $model->id }}" class="btn btn-sm btn-danger hover-scale me-2 btn-delete" style="background-color: #D32F2F" title="Hapus">
        <i class="fas fa-trash-alt fs-4 me-2"></i>Hapus
    </a>
    @endif

    @if($isUnserviceable)
    <a href="/parachute-inspection/print-tag/{{ $model->id }}" target="_blank" class="btn btn-sm btn-success hover-scale" style="background-color: #673AB7" data-id="{{ $model->id }}">
        <i class="fas fa-print fs-4 me-2"></i> Cetak
    </a>
    @endif
</div>
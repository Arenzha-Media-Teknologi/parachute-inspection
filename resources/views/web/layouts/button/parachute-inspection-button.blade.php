@php

$permission = json_decode(Auth::user()->user_groups->permissions);

@endphp

<div class="text-center">
    @if(in_array("edit_parachute_check", $permission))
    <!-- <a href="#" class="btn btn-sm btn-warning hover-scale btn-detail me-2" data-id="{{ $model->id }}" data-bs-toggle="modal" data-bs-target="#kt_modal_detail"> -->
    <a href="/parachute-inspection/edit/{{ $model->id }}" class="btn btn-sm btn-warning hover-scale me-2" data-id="{{ $model->id }}">
        <i class="fas fa-file-alt fs-4 me-2"></i> Riwayat
    </a>
    @endif

    @if(in_array("delete_parachute_check", $permission))
    <a href="#" data-id="{{ $model->id }}" class="btn btn-sm btn-danger hover-scale btn-delete" title="Hapus">
        <i class="fas fa-trash-alt fs-4 me-2"></i>Hapus
    </a>
    @endif
</div>
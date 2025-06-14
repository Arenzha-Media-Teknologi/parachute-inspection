@php

$permission = json_decode(Auth::user()->user_groups->permissions);

@endphp

<div class="text-center">
    @if(in_array("edit_parachute", $permission))
    <a href="#" class="btn btn-sm hover-scale btn-warning me-2 btn-edit-parasut"
        data-id="{{ $model->id }}"
        data-bs-toggle="modal"
        data-bs-target="#kt_modal_edit" style="background-color: #607D8B">
        <i class="fas fa-pencil-alt fs-4 me-2"></i>Edit
    </a>
    @endif

    @if(in_array("delete_parachute", $permission))
    <a href="#" data-id="{{ $model->id }}" class="btn btn-sm btn-danger hover-scale btn-delete" style="background-color: #D32F2F" title="Hapus">
        <i class="fas fa-trash-alt fs-4 me-2"></i>Hapus
    </a>
    @endif
</div>
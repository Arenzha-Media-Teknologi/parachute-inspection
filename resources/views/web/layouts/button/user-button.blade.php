@php

$permission = json_decode(Auth::user()->user_groups->permissions);

@endphp

<div class="text-center">
    @if(in_array("edit_user", $permission))
    <a href="/user/edit/{{ $model->id }}" class="btn btn-sm hover-scale btn-warning me-2 btn-edit-user">
        <i class="fas fa-pencil-alt fs-4 me-2"></i>Edit
    </a>
    @endif
    @if(in_array("delete_user", $permission))
    <a href="#" data-id="{{ $model->id }}" class="btn btn-sm btn-danger hover-scale btn-delete" title="Hapus">
        <i class="fas fa-trash-alt fs-4 me-2"></i>Hapus
    </a>
    @endif
</div>
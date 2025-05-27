@php

$permission = json_decode(Auth::user()->user_groups->permissions);

@endphp

<div class="text-center">
    @if(in_array("edit_user_group", $permission))
    <a href="/group/edit/{{ $model->id }}" class="btn btn-sm hover-scale btn-warning me-2" style="background-color: #607D8B" title="Edit">
        <i class="fas fa-pencil-alt fs-4 me-2"></i>Edit
    </a>
    @endif

    @if(in_array("delete_user_group", $permission))
    <a href="#" data-id="{{ $model->id }}" class="btn btn-sm btn-danger hover-scale btn-delete" style="background-color: #D32F2F" title="Hapus">
        <i class="fas fa-trash-alt fs-4 me-2"></i>Hapus
    </a>
    @endif
</div>
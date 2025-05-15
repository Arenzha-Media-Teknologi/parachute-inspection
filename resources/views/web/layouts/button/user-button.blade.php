<div class="text-center">
    <a href="#" class="btn btn-sm hover-scale btn-warning me-2 btn-edit-user"
        data-id="{{ $model->id }}"
        data-bs-toggle="modal"
        data-bs-target="#kt_modal_edit">
        <i class="fas fa-pencil-alt fs-4 me-2"></i>Edit
    </a>

    <a href="#" data-id="{{ $model->id }}" class="btn btn-sm btn-danger hover-scale btn-delete" title="Hapus">
        <i class="fas fa-trash-alt fs-4 me-2"></i>Hapus
    </a>
</div>
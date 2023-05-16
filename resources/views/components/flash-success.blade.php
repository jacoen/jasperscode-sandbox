@if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong>
        {{ $message }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="close"></button>
    </div>
@endif
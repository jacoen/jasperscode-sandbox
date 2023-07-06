@props(['message'])

@if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span class="fw-bold">Success!</span>
        {{ $message }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="close"></button>
    </div>
@endif
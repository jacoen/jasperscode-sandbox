@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-2" role="alert">
        <span class="fw-semibold">It looks like something went wrong</span>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
@endif

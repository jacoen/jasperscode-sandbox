<div class="d-flex justify-content-between align-items-center clearfix">
    <div class="pl-2">
        <span class="fw-semibold">
            {{ $records->count() }} out of {{ $records->total() }} results
        </span>
    </div>
    <div class="justify-content-start">
        {{ $records->links() }}
    </div>
</div>
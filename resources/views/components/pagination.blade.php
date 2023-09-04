@props(['records'])


<div class="d-flex justify-content-between align-items-center clearfix">
    <div class="pl-2">
        @if (! $records->count())
            <span class="fw-semibold">
                No results
            </span>
        @else
            <span class="fw-semibold">
                {{ $records->firstItem() }} to {{ $records->lastItem() }} out of {{ $records->total() }} results
            </span>
        @endif
    </div>
    <div class="justify-content-start">
        {{ $records->withQueryString()->links() }}
    </div>
</div>
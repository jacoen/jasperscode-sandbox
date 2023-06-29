@props(['route', 'param', 'content', 'limit'])

<td>
    <a href="{{ route($route, $param) }}" class="text-decoration-none text-reset fw-semibold">
        <span title="{{ $content }}">
            {{ Str::limit($content, $limit) }}
        </span>
    </a>
</td>
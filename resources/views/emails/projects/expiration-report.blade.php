<x-mail::message>
# hallo {{ $user }},

Via dit bericht willen wij u ervan op de hoogte brengen dat de eind datum van {{ $count }} afgelopen week verlopen zijn.
Wanneer u op de knop hieronder drukt dan kunt het overzicht van deze verlopen projecten zien

<x-mail::button :url="{{ route('projects.expired') }}">
Naar overzicht
</x-mail::button>

Wij hopen dat wij u hiermee voldoende hebben geïnformeerd.

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>

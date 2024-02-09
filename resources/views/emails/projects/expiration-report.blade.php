<x-mail::message>
# hallo {{ $user }},

Via dit bericht willen wij u ervan op de hoogte brengen dat de eind datum van een aantal projecten de afgelopen week verstreken is.
In totaal is de eind datum van {{ $count }} projecten verlopen,
deze projecten kunt u vanaf heden terug vinden in het overzicht van verlopen projecten.
Wanneer u op de knop in deze mail klikt wordt u naar het overzicht van verlopen projecten gebracht.

<x-mail::button :url="route('projects.expired', ['yearWeek' => $yearWeek])">
Naar overzicht
</x-mail::button>

Wij hopen dat wij u hiermee voldoende hebben ge&iuml;nformeerd.

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>

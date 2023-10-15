<x-mail::message>
# Hallo {{ $user }}

Via dit bericht willen wij u op de hoogte brengen dat de eind datum van uw project binnen een maand verstrijkt.<br />
De eind datum van het project {{ $project->title }} is {{ $project->due_date->format('d-m-Y')}}.<br />

Wij hopen dat u hiermee voldoende bent ge&Iuml;nformeerd.<br />

Met vriendelijke groet,<br>
{{ config('app.name') }}
</x-mail::message>

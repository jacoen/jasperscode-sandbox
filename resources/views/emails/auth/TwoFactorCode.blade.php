<x-mail::message>
# Hallo {{ $user }},

Hier ontvangt u uw twee staps verificatie code.
Uw code is <br>
<code>{{ $two_factor_code }}</code>.
Deze code zal over 5 minuten verlopen. 
Als u niet probeerde in te loggen kunt u dit bericht negeren.

Met vriendelijke groet,<br>

{{ config('app.name') }}
</x-mail::message>
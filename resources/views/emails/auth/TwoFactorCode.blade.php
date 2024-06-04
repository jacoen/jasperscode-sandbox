<x-mail::message>
# {{ __('emails.common.greeting', ['user' => $user]) }},

{{ __('emails.two-factor.intro') }}
{{ __('emails.two-factor.code-label')}}  
**{{ $two_factor_code }}**<br>
{{ __('emails.two-factor.expiration') }}
{{ __('emails.two-factor.ignore') }}

{{ __('emails.common.regards') }}  

{{ config('app.name') }}
</x-mail::message>
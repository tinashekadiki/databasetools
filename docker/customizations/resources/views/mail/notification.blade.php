<x-mail::message>
# DOT Database Tools: {{ $title }}

{{ $body }}

<x-mail::panel>
@foreach($fields as $label => $value)
**{{ $label }}:** {{ $value }}<br>
@endforeach
**Time:** {{ $footerText }}
</x-mail::panel>

@if($errorMessage !== null)
## {{ $errorLabel ?? __('Error Details') }}

<x-mail::panel>
{{ $errorMessage }}
</x-mail::panel>
@endif

<x-mail::button :url="$actionUrl" :color="$buttonColor">
{{ $actionText }}
</x-mail::button>

---

{{ __('This is an automated DOT Database Tools notification.') }}@if($actionRequired) {{ __('Please investigate the issue and take appropriate action.') }}@endif

Developed by DOT (dots.co.zw)<br>
Upstream Databasement attribution is available on the Licenses page.
</x-mail::message>

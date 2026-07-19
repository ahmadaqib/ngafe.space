<x-mail::message>
# Keputusan keberatan konten

Keputusan: **{{ $appeal->status === 'content_removed' ? 'konten diturunkan' : 'konten dipertahankan' }}**

{{ $appeal->decision }}

@if($appeal->appeal_count === 0)
Kamu dapat mengajukan satu kali banding melalui tautan berikut.

<x-mail::button :url="route('content-appeal-status', $appeal)">Lihat keputusan / ajukan banding</x-mail::button>
@endif
</x-mail::message>

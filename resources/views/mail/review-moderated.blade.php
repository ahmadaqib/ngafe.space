<x-mail::message>
# Status reviewmu

Review di **{{ $review->cafe->name }}** telah diputuskan: **{{ $decision === 'approve' ? 'ditayangkan' : 'diturunkan' }}**.

Alasan: {{ $reason }}

Kalau menurutmu keputusan ini keliru, balas email ini untuk meminta peninjauan.

Terima kasih,<br>
ngafe.space
</x-mail::message>

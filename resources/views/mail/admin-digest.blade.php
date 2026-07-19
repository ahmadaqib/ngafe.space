<x-mail::message>
# Antrian moderasi hari ini

- Review pending: {{ $pendingReviews }}
- Report terbuka: {{ $openReports }}
- Keberatan konten: {{ $openAppeals }}

<x-mail::button :url="url('/ruang-admin')">Buka ruang admin</x-mail::button>
</x-mail::message>

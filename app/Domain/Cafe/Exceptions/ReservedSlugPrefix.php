<?php

namespace App\Domain\Cafe\Exceptions;

use App\Exceptions\DomainException;

/**
 * Docs/Spec.md §10 SEO uses /{city}/cafe-{category-slug} for category+city
 * pages (Docs/plan.md Task 5.1). A cafe slug starting with "cafe-" would be
 * shadowed by that route and become unreachable, so it's rejected at the
 * model layer — the one place every creation path (Filament today, F8 user
 * proposals later) is guaranteed to go through.
 */
class ReservedSlugPrefix extends DomainException
{
    public function userMessage(): string
    {
        return 'Slug cafe nggak boleh diawali "cafe-" — awalan itu dipakai untuk halaman kategori.';
    }
}

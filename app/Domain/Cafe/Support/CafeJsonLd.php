<?php

namespace App\Domain\Cafe\Support;

use App\Domain\Cafe\Models\Cafe;

/**
 * Builds schema.org LocalBusiness + AggregateRating JSON-LD for a cafe
 * detail page (Docs/plan.md Task 5.1, Docs/Spec.md §10 SEO). Numbers stay
 * machine-readable (periods), unlike the locale-ID display formatting in
 * App\Support\Format — schema.org is a different layer (§12.5).
 */
final class CafeJsonLd
{
    /** @return array<string, mixed> */
    public static function build(Cafe $cafe, string $canonicalUrl, ?string $imageUrl): array
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'CafeOrCoffeeShop',
            'name' => $cafe->name,
            'url' => $canonicalUrl,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $cafe->address,
                'addressLocality' => ucfirst($cafe->city),
                'addressCountry' => 'ID',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $cafe->lat,
                'longitude' => (float) $cafe->lng,
            ],
        ];

        if ($imageUrl) {
            $jsonLd['image'] = $imageUrl;
        }

        if ($cafe->price_range) {
            $jsonLd['priceRange'] = 'Rp '.$cafe->price_range.'rb';
        }

        if ($cafe->rating_count > 0) {
            $jsonLd['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $cafe->rating_avg,
                'reviewCount' => $cafe->rating_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $jsonLd;
    }
}

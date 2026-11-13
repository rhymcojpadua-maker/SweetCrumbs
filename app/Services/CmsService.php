<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;

class CmsService
{
    public function homepage(): array
    {
        $defaults = [
            'hero_eyebrow' => 'Crafted for life\'s sweetest moments',
            'hero_heading' => 'Every celebration deserves a cake with a story.',
            'hero_subheading' => 'Discover independent bakeries, design your perfect cake, and reserve a pickup or delivery across the Philippines.',
            'hero_primary_label' => 'Explore cakes',
            'hero_primary_url' => '/cakes',
            'hero_secondary_label' => 'Meet the bakers',
            'hero_secondary_url' => '/bakeries',
            'hero_image' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=1600&q=80',
            'promo_heading' => 'A cake for every kind of celebration',
            'promo_body' => 'From intimate birthdays to once-in-a-lifetime weddings, SweetCrumbs brings the Philippines\' most thoughtful home bakers into one beautiful marketplace.',
            'promo_cta_label' => 'Start exploring',
            'promo_cta_url' => '/cakes',
        ];

        return array_merge($defaults, $this->global('homepage'));
    }

    public function page(string $slug): ?object
    {
        return $this->entry('pages', $slug);
    }

    public function faqs(): Collection
    {
        return $this->collection('faqs')->map(fn ($entry) => [
            'question' => $this->value($entry, 'question'),
            'answer' => $this->value($entry, 'answer'),
        ])->filter(fn ($item) => $item['question']);
    }

    public function testimonials(): Collection
    {
        return $this->collection('testimonials')->map(fn ($entry) => [
            'quote' => $this->value($entry, 'quote'),
            'name' => $this->value($entry, 'name'),
            'detail' => $this->value($entry, 'detail'),
            'rating' => (int) ($this->value($entry, 'rating') ?: 5),
        ])->filter(fn ($item) => $item['quote']);
    }

    public function collection(string $handle): Collection
    {
        try {
            return Entry::query()
                ->where('collection', $handle)
                ->where('published', true)
                ->orderBy('order')
                ->get();
        } catch (\Throwable $exception) {
            Log::debug('Statamic collection unavailable', ['collection' => $handle, 'error' => $exception->getMessage()]);

            return collect();
        }
    }

    public function global(string $handle): array
    {
        try {
            $set = GlobalSet::findByHandle($handle);
            if (! $set) {
                return [];
            }

            $localized = match (true) {
                method_exists($set, 'inCurrentSite') => $set->inCurrentSite(),
                method_exists($set, 'inDefaultSite') => $set->inDefaultSite(),
                default => $set,
            };

            return $localized?->data()?->all() ?? [];
        } catch (\Throwable $exception) {
            Log::debug('Statamic global unavailable', ['global' => $handle, 'error' => $exception->getMessage()]);

            return [];
        }
    }

    public function entry(string $collection, string $slug): ?object
    {
        try {
            return Entry::query()
                ->where('collection', $collection)
                ->where('slug', $slug)
                ->where('published', true)
                ->first();
        } catch (\Throwable $exception) {
            Log::debug('Statamic entry unavailable', ['collection' => $collection, 'slug' => $slug, 'error' => $exception->getMessage()]);

            return null;
        }
    }

    public function value(?object $entry, string $key, mixed $default = null): mixed
    {
        return $entry?->get($key) ?? $default;
    }
}

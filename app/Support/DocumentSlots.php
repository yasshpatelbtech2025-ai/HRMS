<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The document slots on the Documents page, built from config/documents.php
 * and the companies.
 */
final class DocumentSlots
{
    /**
     * @return Collection<string, array{title: string, short: string, slots: list<array{slug: string, name: string, company_id: ?int}>}> by category key
     */
    public static function all(): Collection
    {
        $companies = Company::orderBy('establishment_name')->get(['id', 'establishment_name']);

        return collect(config('documents'))->map(function (array $category, string $key) use ($companies) {
            $slots = [];

            foreach ($category['documents'] as $template) {
                foreach ($category['per_company'] ? $companies : [null] as $company) {
                    $name = $company ? str_replace(':company', $company->establishment_name, $template) : $template;
                    $slots[] = [
                        'slug' => $key.'-'.($company ? $company->id.'-' : '').Str::slug($name),
                        'name' => $name,
                        'company_id' => $company?->id,
                    ];
                }
            }

            return ['title' => $category['title'], 'short' => $category['short'], 'slots' => $slots];
        });
    }

    /**
     * @return array{category: string, slug: string, name: string, company_id: ?int}|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $key => $category) {
            foreach ($category['slots'] as $slot) {
                if ($slot['slug'] === $slug) {
                    return ['category' => $key] + $slot;
                }
            }
        }

        return null;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Resolves config/navigation.php against the current request: active
 * states for the sidebar, the breadcrumb trail and the palette's page list.
 */
class Navigation
{
    /** @return array<int, array{label: ?string, items: array}> */
    public function groups(): array
    {
        return collect(config('navigation'))->map(function (array $group) {
            $group['items'] = collect($group['items'])->map(function (array $item) {
                $item['children'] = collect($item['children'] ?? [])
                    ->map(fn (array $child) => $child + ['active' => $this->isActive($child)])
                    ->all();
                $item['active'] = $this->isActive($item);

                return $item;
            })->all();

            return $group;
        })->all();
    }

    /** @return array<int, array{label: string, url: ?string}> */
    public function breadcrumbs(): array
    {
        foreach ($this->groups() as $group) {
            foreach ($group['items'] as $item) {
                if (! $item['active']) {
                    continue;
                }

                $trail = [['label' => __($item['label']), 'url' => route($item['route'])]];
                foreach ($item['children'] as $child) {
                    // The section's own landing page ("Employee list") adds nothing to the trail.
                    if ($child['active'] && $child['route'] !== $item['route']) {
                        $trail[] = ['label' => __($child['label']), 'url' => route($child['route'])];
                    }
                }

                return $trail;
            }
        }

        return [];
    }

    /** Flat list of every page for the command palette. */
    public function pages(): array
    {
        $pages = [];
        foreach (config('navigation') as $group) {
            foreach ($group['items'] as $item) {
                $pages[] = $this->page($item, $group['label']);
                foreach ($item['children'] ?? [] as $child) {
                    if ($child['route'] !== $item['route']) {
                        $pages[] = $this->page($child, __($item['label']));
                    }
                }
            }
        }

        return $pages;
    }

    private function page(array $item, ?string $section): array
    {
        return [
            'label' => __($item['label']),
            'section' => $section ? __($section) : null,
            'url' => route($item['route']),
            'keywords' => $item['keywords'] ?? '',
        ];
    }

    private function isActive(array $item): bool
    {
        return Route::currentRouteNamed(...($item['match'] ?? [$item['route']]));
    }
}

<?php

namespace SolutionForest\FilamentAccessManagement\Support;

use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use SolutionForest\FilamentAccessManagement\Navigation\NavigationItem;
use SolutionForest\FilamentTree;
use SolutionForest\FilamentTree\Concern\SupportTranslation;

class Menu
{
    use SupportTranslation {
        SupportTranslation::handleTranslatable as traitHandleTranslatable;
    }

    /**
     * Get or create a navigation item from db.
     */
    public static function createNavigation(string $title,
        ?int $parent = null,
        ?string $icon= null,
        ?string $activeIcon= null,
        ?string $uri= null,
        ?string $badge= null,
        ?string $badgeColor= null,
        bool $isFilamentPanel= false): Model
    {
        return Utils::getMenuModel()::firstOrCreate(
            [
                'title' => $title,
                'parent_id' => $parent ?? -1,
                'is_filament_panel' => $isFilamentPanel,
            ],
            [
                'icon' => $icon,
                'active_icon' => $activeIcon,
                'uri' => $uri,
                'badge' => $badge,
                'badge_color' => $badgeColor,
            ]
        );
    }

    /**
     * Get a navigation item from db.
     */
    public static function getNavigation(string $title, int $parent): ?Model
    {
        return Utils::getMenuModel()::query()
            ->where('title', $title)
            ->where('parent_id', $parent)
            ->first();
    }

    /**
     * Get all navigation items from db.
     */
    public static function getAllNavigation(): Collection
    {
        return Cache::remember(
            static::getCacheKey(),
            static::getCacheExpirationTime(),
            fn () => collect(Utils::getMenuModel()::ordered()->get())
        );
    }

    /**
     * Get filament navigation group.
     *
     * @return Collection<string,NavigationGroup>
     */
    public static function getNavigationGroups()
    {
        $model = app(Utils::getMenuModel());
        $nodes = static::getAllNavigation();

        $titleColumnName = method_exists($model, 'determineTitleColumnName') ? $model->determineTitleColumnName() : 'title';
        $childrenKeyName = FilamentTree\Support\Utils::defaultChildrenKeyName();

        $tree = [];

        if (method_exists($model, 'toTree')) {
            $tree = $model->toTree($nodes);
        } else {
            $tree = FilamentTree\Support\Utils::buildNestedArray(
                nodes: static::getAllNavigation(),
                parentId: null,
                primaryKeyName: method_exists($model, 'getKeyName') ? $model->getKeyName() : null,
                parentKeyName: method_exists($model, 'determineParentColumnName')? $model->determineParentColumnName() : null,
                childrenKeyName: $childrenKeyName,
            );
        }

        $result = collect();

        foreach ($tree as $index => $item) {
            static::handleTranslatable($item);

            $navGroupLabel = static::ensureNavigationLabel(empty($item[$childrenKeyName]) ? null : $item[$titleColumnName]);

            $nodes = collect($item)->toArray();

            if (empty($nodes)) {
                continue;
            }

            $icon = null;
            $childrenNodes = [];
            // Is Navigation Group
            if (! empty($navGroupLabel)) {
                $iconColumnName = method_exists($model, 'determineIconColumnName') ? $model->determineIconColumnName() : 'icon';
                $icon = $item[$iconColumnName] ?? null;
                $childrenNodes = $item[$childrenKeyName] ?? [];
            } else {
                $childrenNodes[] = $nodes;
            }
            $navigationGroupItems = static::buildNavigationGroupItems($childrenNodes, $navGroupLabel, $icon);

            $result->put(
                $navGroupLabel ?? $index,
                NavigationGroup::make()
                    ->label($navGroupLabel)
                    ->icon($icon)
                    ->items($navigationGroupItems)
            );
        }
        return $result;
    }

    public static function clearCache(): void
    {
        Cache::forget(static::getCacheKey());
    }

    public static function getCacheKey(): string
    {
        return config('filament-access-management.cache.navigation.key', 'filament_navigation');
    }


    public static function getCacheExpirationTime(): \DateInterval|int
    {
        return config('filament-access-management.cache.navigation.expiration_time') ?: \DateInterval::createFromDateString('24 hours');
    }

    private static function handleTranslatable(array &$final): void
    {
        $modelClass = Utils::getMenuModel();
        if (method_exists($modelClass, 'handleTranslatable')) {
            $modelClass::handleTranslatable($final);
        } else {
            static::traitHandleTranslatable($final, $modelClass);
        }
    }

    private static function ensureNavigationLabel($label): ?string
    {
        if (! $label) {
            return $label;
        }
        if (is_array($label) || $label instanceof Arrayable) {
            return collect($label)->first();
        }
        if (! is_string($label)) {
            return (string) $label;
        }
        return $label;
    }

    private static function buildNavigationGroupItems(array $treeItems = [], ?string $groupLabel = null, ?string $groupIcon = null): array
    {
        if (empty($treeItems)) {
            return [];
        }

        $model = app(Utils::getMenuModel());
        return collect($treeItems)
            ->map(function (array $treeItem) {
                static::handleTranslatable($treeItem);
                return $treeItem;
            })
            ->map(function (array $treeItem) use ($model) {

                $labelColumnName = method_exists($model, 'determineTitleColumnName') ? $model->determineTitleColumnName() : 'title';
                $iconColumnName = method_exists($model, 'determineIconColumnName') ? $model->determineIconColumnName() : 'icon';
                $activeIconColumnName = method_exists($model, 'determineActiveIconColumnName') ? $model->determineActiveIconColumnName() : 'active_icon';
                $uriColumnName = method_exists($model, 'determineUriColumnName') ? $model->determineUriColumnName() : 'uri';
                $badgeColumnName = method_exists($model, 'determineBadgeColumnName') ? $model->determineBadgeColumnName() : 'badge';
                $badgeColorColumnName = method_exists($model, 'determineBadgeColorColumnName') ? $model->determineBadgeColorColumnName() : 'badge_color';
                $orderColumnName = method_exists($model, 'determineOrderColumnName') ? $model->determineOrderColumnName() : FilamentTree\Support\Utils::orderColumnName();

                $url = trim(($treeItem[$uriColumnName] ?? "/"), '/');

                if (($treeItem['is_filament_panel'] ?? false) == true && $panel = (filament()->getCurrentPanel() ?? filament()->getDefaultPanel())) {

                    $pathInPanel = (string)str($panel->getPath())
                        ->trim('/')
                        ->append('/')
                        ->when($panel->hasTenancy(),
                            fn ($str) => $str
                                ->append(filament()->getTenant()?->getKey())
                                ->append('/'))
                        ->append($url);

                    $url = url($pathInPanel);
                }

                return NavigationItem::make()
                    ->label(static::ensureNavigationLabel($treeItem[$labelColumnName]) ?? "")
                    ->group($groupLabel ?? "")
                    ->groupIcon($groupIcon ?? "")
                    ->icon($treeItem[$iconColumnName] ?? Utils::getFilamentDefaultIcon())   // must have icon
                    ->activeIcon($treeItem[$activeIconColumnName] ?? "")
                    ->isActiveWhen(fn (): bool => request()->is(trim(($treeItem[$uriColumnName] ?? "/"), '/')))
                    ->sort(intval($treeItem[$orderColumnName] ?? 0))
                    ->badge(($treeItem[$badgeColumnName] ?? null), color: ($treeItem[$badgeColorColumnName] ?? null))
                    ->url($url);
            })->toArray();
    }
}

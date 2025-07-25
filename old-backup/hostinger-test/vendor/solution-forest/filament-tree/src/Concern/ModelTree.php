<?php

namespace SolutionForest\FilamentTree\Concern;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use SolutionForest\FilamentTree\Concern\SupportTranslation;
use SolutionForest\FilamentTree\Support\Utils;

trait ModelTree
{
    use SupportTranslation {
        SupportTranslation::handleTranslatable as traitHandleTranslatable;
    }

    public function initializeModelTree()
    {
        if (!empty($this->getFillable())) {
            $this->mergeFillable([
                $this->determineOrderColumnName(),
                $this->determineParentColumnName(),
                $this->determineTitleColumnName(),
            ]);
        }
    }

    /**
     * Boot the bootable traits on the model.
     */
    public static function bootModelTree()
    {
        static::saving(function(Model $model) {
            if (empty($model->{$model->determineParentColumnName()}) || $model->{$model->determineParentColumnName()} === -1) {
                $model->{$model->determineParentColumnName()} = static::defaultParentKey();
            }
            if (empty($model->{$model->determineOrderColumnName()}) || $model->{$model->determineOrderColumnName()} === 0) {
                $model->setHighestOrderNumber();
            }
        });

        // Delete children
        static::deleting(function (Model $model) {
            static::buildSortQuery()
                ->where($model->determineParentColumnName(), $model->getKey())
                ->get()
                ->each
                ->delete();
        });
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, $this->determineParentColumnName())->with('children')->orderBy($this->determineOrderColumnName());
    }

    public function isRoot(): bool
    {
        return $this->getAttributeValue($this->determineParentColumnName()) === static::defaultParentKey();
    }

    public function setHighestOrderNumber(): void
    {
        $this->{$this->determineOrderColumnName()} = $this->getHighestOrderNumber() + 1;
    }

    public function getHighestOrderNumber(): int
    {
        return (int) $this->buildSortQuery()->where($this->determineParentColumnName(), $this->{$this->determineParentColumnName()})->max($this->determineOrderColumnName());
    }

    public function getLowestOrderNumber(): int
    {
        return (int) $this->buildSortQuery()->where($this->determineParentColumnName(), $this->{$this->determineParentColumnName()})->min($this->determineOrderColumnName());
    }

    public function scopeOrdered(Builder $query, string $direction = 'asc')
    {
        return $query->orderBy($this->determineParentColumnName(), 'asc')->orderBy($this->determineOrderColumnName(), $direction);
    }

    public function scopeIsRoot(Builder $query)
    {
        return $query->where($this->determineParentColumnName(), static::defaultParentKey());
    }

    public function determineOrderColumnName() : string
    {
        return Utils::orderColumnName();
    }

    public function determineParentColumnName() : string
    {
        return Utils::parentColumnName();
    }

    public function determineTitleColumnName() : string
    {
        return Utils::titleColumnName();
    }

    public static function defaultParentKey()
    {
        return Utils::defaultParentId();
    }

    public static function defaultChildrenKeyName(): string
    {
        return Utils::defaultChildrenKeyName();
    }

    /**
     * Format all nodes as tree.
     *
     * @param array|\Illuminate\Support\Collection|null $nodes
     */
    public function toTree($nodes = null): array
    {
        if ($nodes === null) {
            $nodes = static::allNodes();
        }

        return Utils::buildNestedArray(
            nodes: $nodes,
            parentId: static::defaultParentKey(),
            primaryKeyName: $this->getKeyName(),
            parentKeyName: $this->determineParentColumnName()
        );
    }

    /**
     * Get tree nodes options.
     */
    public static function treeNodes(): array
    {
        $result = [];

        $model = app(static::class);

        [$primaryKeyName, $titleKeyName, $parentKeyName, $childrenKeyName] = [
            $model->getKeyName(),
            $model->determineTitleColumnName(),
            $model->determineParentColumnName(),
            static::defaultChildrenKeyName(),
        ];

        $nodes = Utils::buildNestedArray(
            nodes: static::allNodes(),
            parentId: static::defaultParentKey(),
            primaryKeyName: $primaryKeyName,
            parentKeyName: $parentKeyName,
            childrenKeyName: $childrenKeyName
        );

        foreach ($nodes as $node) {
            static::buildTreeNodeItem($result, $node, $primaryKeyName, $titleKeyName, $childrenKeyName);
        }

        return $result;
    }

    /**
     * Get select array options.
     */
    public static function selectArray(?int $maxDepth = null): array
    {
        $result = [];

        $model = app(static::class);

        [$primaryKeyName, $titleKeyName, $parentKeyName, $childrenKeyName] = [
            $model->getKeyName(),
            $model->determineTitleColumnName(),
            $model->determineParentColumnName(),
            static::defaultChildrenKeyName(),
        ];

        $nodes = Utils::buildNestedArray(
            nodes: static::allNodes(),
            parentId: static::defaultParentKey(),
            primaryKeyName: $primaryKeyName,
            parentKeyName: $parentKeyName,
            childrenKeyName: $childrenKeyName
        );

        $result[static::defaultParentKey()] = __('filament-tree::filament-tree.root');

        foreach ($nodes as $node) {
            static::buildSelectArrayItem($result, $node, $primaryKeyName, $titleKeyName, $childrenKeyName, 1, $maxDepth);
        }

        return $result;
    }

    /**
     * @return static[]|\Illuminate\Support\Collection
     */
    public static function allNodes()
    {
        return static::buildSortQuery()->get();
    }

    public static function buildSortQuery(): Builder
    {
        return static::query()->ordered();
    }

    public static function handleTranslatable(array &$final): void
    {
        static::traitHandleTranslatable($final, static::class);
    }

    private static function buildSelectArrayItem(array &$final, array $item, string $primaryKeyName, string $titleKeyName, string $childrenKeyName, int $depth, ?int $maxDepth = null): void
    {
        if (! isset($item[$primaryKeyName])) {
            throw new \InvalidArgumentException("Unset '{$primaryKeyName}' primary key.");
        }

        if ($maxDepth && $depth > $maxDepth) {
            return;
        }

        static::handleTranslatable($item);

        $key = $item[$primaryKeyName];
        $title = isset($item[$titleKeyName])? $item[$titleKeyName] : $item[$primaryKeyName];
        if (! is_string($title)) {
            $title = (string) $title;
        }
        $final[$key] = Str::padLeft($title, (str($title)->length() + ($depth * 3)), '-');

        if (count($item[$childrenKeyName])) {
            foreach ($item[$childrenKeyName] as $child) {
                static::buildSelectArrayItem($final, $child, $primaryKeyName, $titleKeyName, $childrenKeyName, $depth + 1, $maxDepth);
            }
        }
    }

    private static function buildTreeNodeItem(array &$final, array $item, string $primaryKeyName, string $titleKeyName, string $childrenKeyName): void
    {
        if (! isset($item[$primaryKeyName])) {
            throw new \InvalidArgumentException("Unset '{$primaryKeyName}' primary key.");
        }
        $pk = data_get($item, $primaryKeyName);
        $name = data_get($item, $titleKeyName);
        $children = [];

        if (count($item[$childrenKeyName])) {
            foreach ($item[$childrenKeyName] as $child) {
                static::buildTreeNodeItem($children, $child, $primaryKeyName, $titleKeyName, $childrenKeyName);
            }
        }
        $final[] = [
            $primaryKeyName => $pk,
            $titleKeyName => $name,
            $childrenKeyName => $children,
        ];
    }
}

<?php

namespace App\Models\Image\Compare;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Объект сравнения двух списков изображений
 */
class Comparator
{
    /**
     * @var Collection
     */
    protected $old;

    /**
     * @var Collection
     */
    protected $new;

    public function __construct(Collection $old, Collection $new)
    {
        $this->guardCollectionType($old);
        $this->guardCollectionType($new);

        $this->old = $old;
        $this->new = $new;
    }

    /**
     * Создать
     *
     * @param array $old
     * @param array $new
     *
     * @return static
     */
    public static function makeFromArray(array $old, array $new)
    {
        $old_col = static::makeSingleCollection($old);
        $new_col = static::makeSingleCollection($new);

        return new static($old_col, $new_col);
    }

    /**
     * @param array $items
     * @return Collection
     */
    protected static function makeSingleCollection(array $items): Collection
    {
        $col = new Collection();
        foreach ($items as $item) {
            $col->add(new Item($item['url'], $item['hash']));
        }

        return $col;
    }

    /**
     * Проверить типы элементов коллекции
     *
     * @param Collection $collection
     */
    protected function guardCollectionType(Collection $collection): void
    {
        foreach ($collection as $item) {
            if (!$item instanceof Item) {
                throw new InvalidArgumentException('Element is not instance of Compare\Item');
            }
        }
    }

    /**
     * Получить список изображения для добавления
     * Сравниваем по урлам
     *
     * @return Collection
     */
    public function getAddList(): Collection
    {
        return $this->new->diffUsing($this->old, function (Item $a, Item $b) {
            return $this->compareByUrl($a, $b);
        });
    }

    /**
     * Получить список изображения для обновления (одинаковые урлы, разные хеши)
     *
     * @return Collection
     */
    public function getUpdateList(): Collection
    {
        $result = new Collection();

        foreach ($this->new as $new) {
            $old = $this->old->where('url', $new->url)->first();
            if (empty($old)) {
                continue;
            }

            if ($old->hash !== $new->hash) {
                $result->add($new);
            }
        }

        return $result;
    }

    /**
     * Получить список изображения для удаления
     * Сравниваем по урлам
     *
     * @return Collection
     */
    public function getDeleteList(): Collection
    {
        return $this->old->diffUsing($this->new, function (Item $a, Item $b) {
            return $this->compareByUrl($a, $b);
        });
    }

    protected function compareByUrl(Item $a, Item $b) {
        return ($a->url === $b->url) ? 0 : -1;
    }
}
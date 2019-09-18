<?php

namespace App\Models\Image\Compare;

use Illuminate\Support\Collection;

class Comparator
{
    protected $old;
    protected $new;

    public function __construct($old, $new)
    {
        $this->old = collect($old);
        $this->new = collect($new);
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
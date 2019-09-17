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

    public function getUpdateList(): Collection
    {
        //TODO Дописать метод получения изображений для обновления

        return $this->new->diffUsing($this->old, function (Item $a, Item $b) {
            if ($a->url === $b->url && $a->url === 'url2') {
                $i = 1;
            }
            return $this->compare($a, $b);
        });
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

    protected function compare(Item $a, Item $b) {
        if ($a->url === $b->url) {
            return ($a->hash === $b->hash) ? 0 : -1 ;
        }

        return 0;
    }

    protected function compareByUrl(Item $a, Item $b) {
        return ($a->url === $b->url) ? 0 : -1;
    }
}
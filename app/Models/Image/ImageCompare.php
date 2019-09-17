<?php

namespace App\Models\Image;

class ImageCompare
{
    protected $old;
    protected $new;

    public function __construct($old, $new)
    {
        $this->old = $old;
        $this->new = $new;
    }

    public function getUpdateList()
    {
        return array_values(array_diff($this->new, $this->old));
    }
}
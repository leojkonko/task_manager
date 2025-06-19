<?php

namespace TaskManager\Model;

class Category
{
    public $id;
    public $name;
    public $color;
    public $description;
    public $user_id;
    public $created_at;
    public $updated_at;

    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['id']) ? $data['id'] : null;
        $this->name = !empty($data['name']) ? $data['name'] : null;
        $this->color = !empty($data['color']) ? $data['color'] : '#007bff';
        $this->description = !empty($data['description']) ? $data['description'] : null;
        $this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = !empty($data['updated_at']) ? $data['updated_at'] : null;
    }

    public function getArrayCopy()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

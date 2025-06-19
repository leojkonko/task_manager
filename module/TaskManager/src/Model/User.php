<?php

namespace TaskManager\Model;

class User
{
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $full_name;
    public $created_at;
    public $updated_at;
    public $status;

    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['id']) ? $data['id'] : null;
        $this->username = !empty($data['username']) ? $data['username'] : null;
        $this->email = !empty($data['email']) ? $data['email'] : null;
        $this->password_hash = !empty($data['password_hash']) ? $data['password_hash'] : null;
        $this->full_name = !empty($data['full_name']) ? $data['full_name'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = !empty($data['updated_at']) ? $data['updated_at'] : null;
        $this->status = !empty($data['status']) ? $data['status'] : 'active';
    }

    public function getArrayCopy()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'password_hash' => $this->password_hash,
            'full_name' => $this->full_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => $this->status,
        ];
    }

    public function setPassword($password)
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
}

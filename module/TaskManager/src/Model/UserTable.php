<?php

namespace TaskManager\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;

class UserTable
{
    private $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function getUser($id)
    {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();
        if (! $row) {
            throw new RuntimeException(sprintf(
                'Could not find row with identifier %d',
                $id
            ));
        }

        return $row;
    }

    public function getUserByUsername($username)
    {
        $rowset = $this->tableGateway->select(['username' => $username]);
        $row = $rowset->current();
        if (! $row) {
            throw new RuntimeException(sprintf(
                'Could not find user with username %s',
                $username
            ));
        }

        return $row;
    }

    public function getUserByEmail($email)
    {
        $rowset = $this->tableGateway->select(['email' => $email]);
        $row = $rowset->current();
        if (! $row) {
            throw new RuntimeException(sprintf(
                'Could not find user with email %s',
                $email
            ));
        }

        return $row;
    }

    public function saveUser(User $user)
    {
        $data = [
            'username' => $user->username,
            'email' => $user->email,
            'password_hash' => $user->password_hash,
            'full_name' => $user->full_name,
            'status' => $user->status,
        ];

        $id = (int) $user->id;

        if ($id === 0) {
            $this->tableGateway->insert($data);
            return;
        }

        try {
            $this->getUser($id);
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf(
                'Cannot update user with identifier %d; does not exist',
                $id
            ));
        }

        $this->tableGateway->update($data, ['id' => $id]);
    }

    public function deleteUser($id)
    {
        $this->tableGateway->delete(['id' => (int) $id]);
    }

    public function authenticateUser($username, $password)
    {
        try {
            $user = $this->getUserByUsername($username);
            if ($user->verifyPassword($password) && $user->status === 'active') {
                return $user;
            }
        } catch (RuntimeException $e) {
            // Usuário não encontrado
        }

        return false;
    }
}

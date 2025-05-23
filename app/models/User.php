<?php

namespace Aries\Dbmodel\Models;

use Aries\Dbmodel\Includes\Database;


class User extends Database {
    private $db;

    public function __construct() {
        parent::__construct(); // Call the parent constructor to establish the connection
        $this->db = $this->getConnection(); // Get the connection instance
    }

    public function getUsers() {
        $sql = "SELECT * FROM users";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUser($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        return $this->db->lastInsertId();
    }

    public function updateUser($data) {
        $sql = "UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        return "Record UPDATED successfully";
    }

    public function deleteUser($id) {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);
        return "Record DELETED successfully";
    }   
}

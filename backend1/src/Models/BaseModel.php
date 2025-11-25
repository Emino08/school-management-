<?php

namespace App\Models;

use App\Config\Database;
use PDO;

abstract class BaseModel
{
    protected $db;
    protected $table;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($conditions = [], $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($limit) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            if ($offset) {
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findOne($conditions)
    {
        $where = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $where[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $fields = array_keys($data);
        $values = ':' . implode(', :', $fields);
        
        // Wrap field names in backticks to handle reserved keywords
        $fieldsStr = '`' . implode('`, `', $fields) . '`';

        $sql = "INSERT INTO {$this->table} ($fieldsStr) VALUES ($values)";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        if (empty($data)) {
            return true;
        }

        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "`$field` = :$field";
            $params[":$field"] = $value;
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
}

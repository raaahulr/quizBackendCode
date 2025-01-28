<?php
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model 
{
    protected $DBGroup = 'default';
    protected $table = 'tbl_users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = ['name', 'email', 'phone_no', 'password'];
    
    // Automatic password hashing
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }
}
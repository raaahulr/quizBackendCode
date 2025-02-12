<?php
namespace App\Models;

use CodeIgniter\Model;

class UserTokenModel extends Model
{
    protected $table = 'user_tokens';
    protected $primaryKey = 'id';
    protected $allowedFields = ['email', 'token', 'expires_at', 'created_at'];
}
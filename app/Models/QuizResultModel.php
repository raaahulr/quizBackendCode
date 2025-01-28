<?php

namespace App\Models;

use CodeIgniter\Model;

class QuizResultModel extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'tbl_quiz_results';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = ['email', 'correct_answers', 'total_questions'];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}
<?php

namespace App\Controllers;

use App\Models\QuizResultModel;
use App\Models\UserTokenModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Quiz extends Controller
{
    private $key;

    public function __construct()
    {
        $this->key = getenv('JWT_SECRET_KEY'); // Load from environment
    }

    public function saveResult()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
        }

        // Get the token from the Authorization header
        $token = $this->request->getHeaderLine('Authorization');

        if (empty($token)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Token not provided'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        try {
            // Decode the token
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));

            // Check if the token is expired
            $userTokenModel = new UserTokenModel();
            $tokenRecord = $userTokenModel->where('token', $token)->first();

            if (!$tokenRecord || strtotime($tokenRecord['expires_at']) < time()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Token has expired'
                ], ResponseInterface::HTTP_UNAUTHORIZED);
            }

            // Proceed with saving the quiz result
            $jsonData = $this->request->getJSON();

            if (!isset($jsonData->email) || !isset($jsonData->correct_answers) || !isset($jsonData->total_questions)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Missing required data'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $quizResultModel = new QuizResultModel();

            $data = [
                'email' => $jsonData->email,
                'correct_answers' => $jsonData->correct_answers,
                'total_questions' => $jsonData->total_questions
            ];

            log_message('info', 'Data to be inserted: ' . json_encode($data));

            if ($quizResultModel->insert($data) === false) {
                log_message('error', 'Insert failed: ' . json_encode($quizResultModel->errors()));
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to save quiz result'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Quiz result saved successfully'
            ], ResponseInterface::HTTP_OK);

        } catch (\Exception $e) {
            log_message('error', 'Quiz result save error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid token or an unexpected error occurred'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }
}
<?php

namespace App\Controllers;

use App\Models\QuizResultModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class Quiz extends Controller
{
    public function saveResult()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
        }

        $jsonData = $this->request->getJSON();
        
        if (!isset($jsonData->email) || !isset($jsonData->correct_answers) || !isset($jsonData->total_questions)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Missing required data'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        try {
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
                'message' => 'An unexpected error occurred'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
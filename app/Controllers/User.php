<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
// In your CodeIgniter controller
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

class User extends Controller {
    
    public function register() 
    {
        // Log the incoming request type and data
        log_message('error', 'Request Method: ' . $this->request->getMethod());
        log_message('error', 'Login Request: ' . json_encode($this->request->getPost()));
        
        // Try different methods to get input data
        $jsonData = $this->request->getJSON();
        $postData = $this->request->getPost();
        $rawInput = $this->request->getRawInput();

        // Log the different input methods
        log_message('error', 'JSON Data: ' . json_encode($jsonData));
        log_message('error', 'POST Data: ' . json_encode($postData));
        log_message('error', 'Raw Input: ' . json_encode($rawInput));

        if ($this->request->getMethod() === 'POST') {
            $model = new UserModel();
            
            // Determine input source
            if (!empty($jsonData)) {
                $name = $jsonData->name ?? '';
                $email = $jsonData->email ?? '';
                $phone_no = $jsonData->phone_no ?? '';
                $password = $jsonData->password ?? '';
            } elseif (!empty($postData)) {
                $name = $postData['name'] ?? '';
                $email = $postData['email'] ?? '';
                $phone_no = $postData['phone_no'] ?? '';
                $password = $postData['password'] ?? '';
            } elseif (!empty($rawInput)) {
                $rawInput = is_string($rawInput) ? json_decode($rawInput, true) : $rawInput;
                $name = $rawInput['name'] ?? '';
                $email = $rawInput['email'] ?? '';
                $phone_no = $rawInput['phone_no'] ?? '';
                $password = $rawInput['password'] ?? '';
            } else {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'No input data received'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Validation rules
            $rules = [
                'name'      => 'required|min_length[3]|max_length[50]',
                'email'     => 'required|valid_email|is_unique[tbl_users.email]',
                'phone_no'  => 'required|min_length[10]|max_length[15]',
                'password'  => 'required|min_length[6]'
            ];

            // Prepare validation data
            $validationData = [
                'name' => $name,
                'email' => $email,
                'phone_no' => $phone_no,
                'password' => $password
            ];

            // Run validation
            if (!$this->validate($rules, $validationData)) {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => $this->validator->getErrors()
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Prepare data for saving
            $data = [
                'name' => $name,
                'email' => $email,
                'phone_no' => $phone_no,
                'password' => $password
            ];

            // Attempt to save user
            try {
                if ($model->save($data)) {
                    return $this->response->setJSON([
                        'statusCode' => 201,
                        'status' => 'success', 
                        'message' => 'Registration successful'
                    ], ResponseInterface::HTTP_CREATED);
                } else {
                    return $this->response->setJSON([
                        'status' => 'error', 
                        'message' => 'Registration failed'
                    ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
                }
            } catch (\Exception $e) {
                log_message('error', 'Registration Error: ' . $e->getMessage());
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'An unexpected error occurred'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // If not a POST request
        return $this->response->setJSON([
            'status' => 'error', 
            'message' => 'Invalid request method'
        ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
    }

    public function login() 
    {
        // Log the incoming request type and data
        log_message('error', 'Request Method: ' . $this->request->getMethod());
        log_message('error', 'Login Request: ' . json_encode($this->request->getPost()));
        
        // Try different methods to get input data
        $jsonData = $this->request->getJSON();
        $postData = $this->request->getPost();
        $rawInput = $this->request->getRawInput();
    
        // Log the different input methods
        log_message('error', 'JSON Data: ' . json_encode($jsonData));
        log_message('error', 'POST Data: ' . json_encode($postData));
        log_message('error', 'Raw Input: ' . json_encode($rawInput));
    
        if ($this->request->getMethod() === 'POST') {
            $model = new UserModel();
            
            // Determine input source
            if (!empty($jsonData)) {
                $email = $jsonData->email ?? '';
                $password = $jsonData->password ?? '';
            } elseif (!empty($postData)) {
                $email = $postData['email'] ?? '';
                $password = $postData['password'] ?? '';
            } elseif (!empty($rawInput)) {
                $rawInput = is_string($rawInput) ? json_decode($rawInput, true) : $rawInput;
                $email = $rawInput['email'] ?? '';
                $password = $rawInput['password'] ?? '';
            } else {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'No input data received'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
    
            // Validation rules
            $rules = [
                'email' => 'required|valid_email',
                'password' => 'required|min_length[6]'
            ];
    
            // Prepare validation data
            $validationData = [
                'email' => $email,
                'password' => $password
            ];
    
            // Run validation
            if (!$this->validate($rules, $validationData)) {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => $this->validator->getErrors()
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
    
            try {
                // Find the user by email
                $user = $model->where('email', $email)->first();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Remove password from response data
                    unset($user['password']);
                    
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'user' => $user
                    ], ResponseInterface::HTTP_OK);
                } else {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Invalid login credentials'
                    ], ResponseInterface::HTTP_UNAUTHORIZED);
                }
            } catch (\Exception $e) {
                log_message('error', 'Login Error: ' . $e->getMessage());
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'An unexpected error occurred'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        // If not a POST request
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Invalid request method'
        ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
    }
}
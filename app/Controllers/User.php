<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User extends Controller
{
    private $key;

    public function __construct()
    {
        $this->key = getenv('JWT_SECRET_KEY'); // Load from environment
    }

    public function register()
    {
        $jsonData = $this->request->getJSON();
        $postData = $this->request->getPost();
        $rawInput = $this->request->getRawInput();

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
                'name' => 'required|min_length[3]|max_length[50]',
                'email' => 'required|valid_email|is_unique[tbl_users.email]',
                'phone_no' => 'required|min_length[10]|max_length[15]',
                'password' => 'required|min_length[6]'
            ];

            // Run validation
            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => $this->validator->getErrors()
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Save user
            try {
                $model->save([
                    'name' => $name,
                    'email' => $email,
                    'phone_no' => $phone_no,
                    'password' => $password
                ]);

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Registration successful'
                ], ResponseInterface::HTTP_CREATED);
            } catch (\Exception $e) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Registration failed'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Invalid request method'
        ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
    }

    public function login()
{
    if ($this->request->getMethod() === 'POST') {
        $jsonData = $this->request->getJSON();
        $postData = $this->request->getPost();
        $rawInput = $this->request->getRawInput();

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

        // Run validation
        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Find user
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Generate JWT
            $payload = [
                'iss' => 'your_issuer',
                'aud' => 'your_audience',
                'iat' => time(),
                'exp' => time() + 3600, // 1 hour expiration
                'data' => [
                    'user_id' => $user['id'],
                    'email' => $user['email']
                ]
            ];

            $jwt = JWT::encode($payload, $this->key, 'HS256');

            // Calculate expiration time
            $expiresAt = date('Y-m-d H:i:s', time() + 10);  

            // Save the token, email, and expiration time in the user_tokens table
            $userTokenModel = new UserTokenModel();
            $userTokenModel->insert([
                'email' => $email,
                'token' => $jwt,
                'expires_at' => $expiresAt
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $jwt
            ], ResponseInterface::HTTP_OK);
        } else {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }

    return $this->response->setJSON([
        'status' => 'error',
        'message' => 'Invalid request method'
    ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
}

    public function validateToken()
    {
        $token = $this->request->getHeaderLine('Authorization');

        if (empty($token)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Token not provided'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        try {
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Token is valid',
                'user' => $decoded->data
            ], ResponseInterface::HTTP_OK);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid token'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }
}
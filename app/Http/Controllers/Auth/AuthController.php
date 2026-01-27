<?php

namespace App\Http\Controllers\Auth;

use App\DTO\Auth\AuthResponseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthServiceInterface;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {}

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request);
        $response = AuthResponseDTO::make($result);

        return response()->json(["message" => "Login successful", "data" => $response]);
    }

    public function logout()
    {
        $this->authService->logout();

        return response()->json(["message" => "Logout successful"]);
    }
}

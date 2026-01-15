<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\DTO\User\UserResponseDTO;
use App\Http\Controllers\Controller;
use App\Services\User\UserServiceInterface;
use App\Http\Requests\User\UserUpdateRequest;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    public function update(UserUpdateRequest $request)
    {
        $result = $this->userService->update($request);
        $response = UserResponseDTO::make($result);

        return response()->json(["message" => "Profile updated successfully", "data" => $response]);
    }

    public function profile(Request $request)
    {
        $result = $this->userService->profile($request);
        $response = UserResponseDTO::make($result);

        return response()->json(["message" => "Profile retrieved successfully", "data" => $response]);
    }
}


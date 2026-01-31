<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\DTO\User\UserResponseDTO;
use App\Http\Controllers\Controller;
use App\Services\User\UserServiceInterface;
use App\Http\Requests\User\UserSearchRequest;
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

    public function search(UserSearchRequest $request)
    {
        $results = $this->userService->search($request);
        $response = UserResponseDTO::collection($results);

        if (empty($response)) {
            return response()->json(["message" => "No users found", "data" => []]);
        }
        
        return response()->json(["message" => "Users retrieved successfully", "data" => $response]);
    }
}

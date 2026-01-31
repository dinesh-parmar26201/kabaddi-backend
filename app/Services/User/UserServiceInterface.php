<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\User\UserSearchRequest;
use App\Http\Requests\User\UserUpdateRequest;

interface UserServiceInterface
{
    public function update(UserUpdateRequest $request): User;

    public function profile(Request $request): User;

    public function search(UserSearchRequest $request);
}

<?php

namespace App\Services\User;

use App\Models\User;
use App\Http\Requests\User\UserUpdateRequest;

class UserService implements UserServiceInterface
{
    public function update(UserUpdateRequest $request): User
    {
        $user = $request->user();
        $user->update([
            'fullname'  => $request->name,
            'phone'     => $request->phone,
            'dob'       => $request->dob,
            'state'     => $request->state,
            'country'   => $request->country,
            'photo'     => $request->photo,
            'fcm_token' => $request->fcm_token,
        ]);

        return $user;
    }

    public function profile($request): User
    {
        return $request->user();
    }
}

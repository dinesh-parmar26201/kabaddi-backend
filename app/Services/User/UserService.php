<?php

namespace App\Services\User;

use Exception;
use App\Models\User;
use App\Http\Requests\User\UserUpdateRequest;

class UserService implements UserServiceInterface
{
    public function update(UserUpdateRequest $request): User
    {
        try {
            $user = $request->user();

            $data = [
                'fullname'  => $request->name,
                'phone'     => $request->phone,
                'dob'       => $request->dob,
                'state'     => $request->state,
                'country'   => $request->country,
                'fcm_token' => $request->fcm_token,
            ];

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('images/Players', 'public');
                if (!$path) {
                    throw new Exception('Photo upload failed');
                }
                $data['photo'] = $path;
            }

            $user->update($data);
            return $user;
        } catch (Exception $e) {
            throw new Exception('User update failed: ' . $e->getMessage());
        }
    }

    public function profile($request): User
    {
        return $request->user();
    }
}

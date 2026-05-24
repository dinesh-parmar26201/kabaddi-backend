<?php

namespace App\Services\User;

use Exception;
use App\Models\User;
use App\Http\Requests\User\UserUpdateRequest;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService implements UserServiceInterface
{
    public function update(UserUpdateRequest $request): User
    {
        try {
            $user = $request->user();

            $data = [
                'fullname'  => $request->name,
                'jersey_no' => $request->jersey_no,
                'phone'     => $request->phone,
                'dob'       => $request->dob,
                'state'     => $request->state,
                'city'      => $request->city,
                'role'      => $request->role,
                'positions' => $request->positions,
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

    public function search($request): LengthAwarePaginator
    {
        $searchTerm = $request->getSearch();
        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return User::where('fullname', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('phone', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('city', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('state', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('country', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('jersey_no', 'LIKE', '%' . $searchTerm . '%')
            ->paginate($perPage);
    }

    public function show(int $id): User
    {
        $user = User::find($id);
        if (!$user) {
            throw new Exception('User not found');
        }
        return $user;
    }
}

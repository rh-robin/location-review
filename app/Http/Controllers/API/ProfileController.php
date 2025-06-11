<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\ResponseTrait;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse;

    public function getPersonalInfo()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated.');
            }

            $profile = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'location' => $user->location,
            ];

            return $this->success($profile, 'User profile retrieved successfully.');

        } catch (\Exception $e) {
            \Log::error('Get Profile Error', ['error' => $e->getMessage()]);
            return $this->error('Something went wrong while fetching profile.', 500, ['error' => $e->getMessage()]);
        }
    }


    public function updatePersonalInfo(Request $request)
    {
        try {
            $user = auth()->user(); // Sanctum uses the default web guard unless configured

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'location' => 'nullable|string|max:200',
            ]);

            $user->update($validatedData);

            $updatedProfile = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'location' => $user->location,
            ];

            return $this->success($updatedProfile, 'Profile updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (\Exception $e) {
            \Log::error('Update Profile Error', ['error' => $e->getMessage()]);
            return $this->error('Something went wrong while updating profile', 500, ['error' => $e->getMessage()]);
        }
    }


    public function changePassword(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $validated = $request->validate([
                'current_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:8',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->error('Current password is incorrect', 403);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return $this->success(null, 'Password changed successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (\Exception $e) {
            \Log::error('Change Password Error', ['error' => $e->getMessage()]);
            return $this->error('Something went wrong while changing password', 500, ['error' => $e->getMessage()]);
        }
    }




}

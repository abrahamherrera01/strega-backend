<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = bcrypt($validatedData['password']);

        $user = User::create($validatedData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'code' => 201,
            'status' => 'success',
            'message' => 'The user has been successfully registered',
            'user' => $user,
            'token' => $token
        ], 201);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {  
            return response()->json([
                'code' => 400,
                'status' => 'error',
                'message' => 'The provided credentials are incorrect.'
            ], 200);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function authenticable(){
        return response()->json([
            "code" => 401,
            "status" => "error",
            "message" => "you are not authenticated"
        ], 200);
    }

    public function getUser(){
        $user = auth()->user();
        
        return response()->json([
            "code" => 200,
            "status" => "success",
            "user" => $user
        ], 200);
    }
}


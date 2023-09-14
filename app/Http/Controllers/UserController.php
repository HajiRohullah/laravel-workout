<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = new User();
            if ($request->itemsPerPage == -1) {
                $query = $query->get();
                return response()->json(['data' => $query, "total" => count($query), 'totalPage' => 1]);
            }
            $query = $query->orderByDesc('created_at');
            $query = $query->paginate($request->itemsPerPage);
            $totalPage = ceil($query->total() / $request->itemsPerPage);
            return response()->json(['data' => $query->items(), "total" => $query->total(), 'totalPage' => $totalPage]);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate(
                [
                    'first_name'             => 'required|min:3',
                    'last_name'              => 'required|min:3',
                    'profile'                => 'required',
                    'username'              => 'required|unique:users,username',
                    'email'                  => 'required|email|unique:users,email',
                    'password'               => 'required|min:6',
                    'confirm_password'       => 'required|min:6',
                ]
            );
            $user = new User();
            $payload = $request->only($user->getFillable());
            if ($request->hasFile('profile'))
                $payload['profile'] = $request->file('profile')->store('public/users-profile');
            $payload["password"]    = bcrypt($payload['password']);
            $user = $user->create($payload);
            return  $user;
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        try {
            $request->validate(
                [
                    'first_name'       => 'required|min:3',
                    'last_name'        => 'required|min:3',
                    'username'        => 'required|unique:users,username,' . $id,
                    'email'            => 'required|email|unique:users,email,' . $id,
                ]
            );
            DB::beginTransaction();
            $user = User::find($id);
            $payload = $request->only($user->getFillable());
            if ($request->hasFile('profile')) {
                if ($user->getRawOriginal('profile')) Storage::delete($user->getRawOriginal('profile'));
                $payload['profile'] = $request->file('profile')->store('public/users-profile');
            } else {
                unset($payload['profile']);
            }
            $user->update($payload);
            DB::commit();
            return  response()->json($user, 202);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $user =  User::find($id);
            $user->delete();
            Storage::delete($user->getRawOriginal('profile') ?? '');
            DB::commit();
            return response()->json(true);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
        //
    }
}

<?php

namespace App\Http\Controllers\Api\Frontend\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UsersListController extends Controller
{
    public function search(Request $request)
    {
        // ✅ Validate query params
        $request->validate([
            'search'   => 'nullable|string|min:1',   // required
            'per_page' => 'sometimes|integer|min:1|max:100', // optional
        ]);

        $query   = trim($request->query('search'));   // use query()
        $perPage = $request->query('per_page', 10);   // default 10

        $authUser = auth()->guard('api')->user();
        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized. Please login.',
            ], 401);
        }

        // ✅ Get base users (exclude self)
        $users = User::role('user', 'api')
            ->where('id', '!=', $authUser->id);

        // ✅ Exact match first
        $exactMatch = (clone $users)->where('name', $query)->get();

        if ($exactMatch->count() > 0) {
            $result = $exactMatch->map(function ($user) use ($authUser) {
                return [
                    'id'           => $user->id,
                    'slug'         => $user->slug,
                    'md5_id'       => md5($user->id),
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'avatar'       => $user->avatar,
                    'is_following' => $authUser->isFollowing($user->id), // ✅ true/false
                ];
            });

            return response()->json([
                'status'       => true,
                'code'         => 200,
                'message'      => 'Data fetch successfully',
                'current_page' => 1,
                'data'         => $result,
                'per_page'     => $perPage,
                'total_user'   => $result->count(),
                'last_page'    => 1,
            ], 200);
        }

        // ✅ Partial match (split query into words)
        $words = preg_split('/\s+/', $query);

        $users->where(function ($qBuilder) use ($words) {
            foreach ($words as $word) {
                $qBuilder->orWhere('name', 'LIKE', "%{$word}%");
            }
        });

        $result = $users->paginate($perPage)->through(function ($user) use ($authUser) {
            return [
                'id'           => $user->id,
                'md5_id'       => md5($user->id),
                'slug'         => $user->slug,
                'name'         => $user->name,
                'avatar'       => $user->avatar,
                'email'        => $user->email,
                'is_following' => $authUser->isFollowing($user->id), // ✅ true/false
            ];
        });

        if ($result->total() === 0) {
            return response()->json([
                'status'       => false,
                'code'         => 404,
                'message'      => 'Data not found',
                'current_page' => 1,
                'data'         => [],
                'per_page'     => $perPage,
                'total_user'   => 0,
                'last_page'    => 1,
            ], 404);
        }

        return response()->json([
            'status'       => true,
            'code'         => 200,
            'message'      => 'Data fetch successfully',
            'data'         => $result->items(),
            'pagination'   => [
                'current_page'      => $result->currentPage(),
                'last_page'         => $result->lastPage(),
                'per_page'          => $result->perPage(),
                'total_user'        => $result->total(),
                'next_page_url'     => $result->nextPageUrl(),
                'previous_page_url' => $result->previousPageUrl(),
            ]
        ], 200);
    }

    // user details

    public function userDetails($slug)
    {
        $authUser = auth()->guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ]);
        }

        $user = User::where('slug', $slug)->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => 'User not found'
            ]);
        }

        // Check if auth user follows the target user
        $isFollowing = DB::table('follows')
            ->where('follower_id', $authUser->id)
            ->where('following_id', $user->id)
            ->exists();

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'User details fetched successfully',
            'data'    => [
                'name'            => $user->name,
                'slug'            => $user->slug,
                'email'           => $user->email,
                'avatar'          => $user->avatar ? asset($user->avatar) : null,
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->followings()->count(),
                'is_following'    => $isFollowing,
            ]
        ]);
    }
}

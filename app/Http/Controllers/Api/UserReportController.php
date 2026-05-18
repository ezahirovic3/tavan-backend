<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserReportController extends Controller
{
    /** POST /users/{user}/report */
    public function store(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'Ne možeš prijaviti samog sebe.');

        $request->validate([
            'reason'      => ['required', 'in:spam,inappropriate,harassment,fake,other'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        UserReport::create([
            'reporter_id' => $request->user()->id,
            'reported_id' => $user->id,
            'reason'      => $request->reason,
            'description' => $request->description,
            'status'      => 'pending',
        ]);

        return response()->json(['message' => 'Prijava je uspješno poslana.']);
    }
}

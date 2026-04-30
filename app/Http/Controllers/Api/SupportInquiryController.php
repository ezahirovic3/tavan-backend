<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportInquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:5000'],
        ]);

        $inquiry = SupportInquiry::create([
            'user_id' => $request->user()?->id,
            'subject' => $data['subject'],
            'body'    => $data['body'],
            'status'  => 'open',
        ]);

        return response()->json(['data' => [
            'id'         => $inquiry->id,
            'subject'    => $inquiry->subject,
            'status'     => $inquiry->status,
            'created_at' => $inquiry->created_at->toISOString(),
        ]], 201);
    }
}

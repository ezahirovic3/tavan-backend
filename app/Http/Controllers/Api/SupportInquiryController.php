<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportInquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Landing page fields
            'name'    => ['nullable', 'string', 'max:150'],
            'email'   => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],

            // Mobile app fields (kept for backwards-compat)
            'subject' => ['nullable', 'string', 'max:255'],
            'body'    => ['nullable', 'string', 'max:5000'],
        ]);

        // Normalise: landing sends `message`, mobile sends `body`
        $body    = $data['message'] ?? $data['body'] ?? '';
        $subject = $data['subject'] ?? Str::limit(strip_tags($body), 80, '…');

        $inquiry = SupportInquiry::create([
            'user_id' => $request->user()?->id,
            'name'    => $data['name'] ?? null,
            'email'   => $data['email'] ?? null,
            'subject' => $subject,
            'body'    => $body,
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

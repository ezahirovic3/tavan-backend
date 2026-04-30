<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'username'            => $this->username,
            'email'               => $this->email,
            'email_verified'      => $this->email_verified_at !== null,
            'avatar'              => $this->avatar,
            'location'            => $this->location,
            'bio'                 => $this->bio,
            'phone'               => $this->phone,
            'phone_verified'      => $this->phone_verified_at !== null,
            'is_verified'               => $this->is_verified,
            'profile_setup_done'        => (bool) ($this->profile_setup_done ?? false),
            'feed_setup_done'           => (bool) ($this->feed_setup_done ?? false),
            'first_listing_coach_seen'  => (bool) ($this->first_listing_coach_seen ?? false),
            'first_draft_coach_seen'    => (bool) ($this->first_draft_coach_seen ?? false),
            'notifications_enabled'     => (bool) ($this->notifications_enabled ?? true),
            'rating'                    => $this->rating,
            'total_reviews'             => $this->total_reviews,
            'item_count'                => $this->when(isset($this->item_count), fn () => (int) $this->item_count),
            'last_active_at'            => $this->last_active_at?->toISOString(),
            'created_at'                => $this->created_at->toISOString(),
        ];
    }
}

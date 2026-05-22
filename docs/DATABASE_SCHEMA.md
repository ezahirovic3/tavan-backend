# Database Schema

## Tables

### users
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| name | varchar(255) | |
| username | varchar(64) unique | |
| email | varchar(255) unique nullable | nullable to support social-only accounts |
| email_verified_at | timestamp null | |
| password | varchar(255) nullable | nullable for social-only accounts |
| google_id | varchar(255) unique null | |
| apple_id | varchar(255) unique null | |
| role | enum('user','admin','super_admin') default 'user' | |
| is_system | boolean default false | reserved for the internal support bot user |
| avatar | varchar(2048) null | Cloudflare R2 URL |
| location | varchar(128) null | city name |
| bio | text null | |
| phone | varchar(32) unique null | |
| phone_verified_at | timestamp null | |
| is_verified | boolean default false | admin-toggled seller badge |
| listings_require_review | boolean default true | all new listings go to pending_review when true |
| notifications_enabled | boolean default true | push notification opt-out |
| profile_setup_done | boolean default false | onboarding flag |
| feed_setup_done | boolean default false | onboarding flag |
| first_listing_coach_seen | boolean default false | onboarding flag |
| first_draft_coach_seen | boolean default false | onboarding flag |
| rating | decimal(3,2) default 0 | cached average from reviews |
| total_reviews | int default 0 | cached review count |
| profile_view_count | unsigned int default 0 | |
| last_active_at | timestamp null | |
| deletion_requested_at | timestamp null | set on account deletion request; cleared on recovery; account anonymized 30 days after this date |
| acquired_via_campaign_id | ULID FK campaigns null | set at registration if a campaign UTM was present |
| remember_token | varchar(100) null | |
| created_at / updated_at | timestamps | |

### user_preferences
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID (FK users) unique | |
| top_sizes | json null | array of size keys |
| bottom_sizes | json null | |
| shoe_sizes | json null | |
| categories | json null | array of category keys |
| subcategories | json null | |
| brands | json null | array of brand ULIDs |
| cities | json null | array of city names |

### user_addresses
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID (FK users) | |
| label | varchar(64) null | e.g. "Kuća", "Posao" |
| name | varchar(255) | recipient name |
| street | varchar(255) | |
| city | varchar(128) | |
| postcode | varchar(16) null | |
| phone | varchar(32) | |
| is_default | boolean default false | |

### user_blocks
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| blocker_id | ULID (FK users) | |
| blocked_id | ULID (FK users) | |
| created_at | timestamp | |
Unique: (blocker_id, blocked_id)

### user_reports
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| reporter_id | ULID (FK users) | |
| reported_id | ULID (FK users) | |
| reason | enum('spam','inappropriate','harassment','fake','other') | |
| description | text null | |
| status | enum('pending','reviewed','dismissed') default 'pending' | |
| created_at / updated_at | timestamps | |

### brands
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| name | varchar(255) | |
| slug | varchar(255) unique | e.g. "zara" |
| logo_url | varchar(2048) null | |
| is_active | boolean default true | |
| is_other | boolean default false | marks the catch-all "Other" brand |
| sort_order | int default 0 | |

### brand_suggestions
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID (FK users) | |
| name | varchar(255) | |
| status | enum('pending','approved','rejected') default 'pending' | |
| created_at / updated_at | timestamps | |

> **Note:** `categories` and `shipping_options` tables exist in the database from early migrations but are not used by the API or admin panel. Categories and shipping/delivery options are defined statically in the mobile client (`src/constants/`).

### products
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| seller_id | ULID (FK users) | |
| brand_id | ULID null (FK brands) | |
| title | varchar(255) nullable | nullable while in draft |
| description | text null | |
| root_category | enum('women','men') null | |
| category | varchar(128) null | section key |
| subcategory | varchar(128) null | item key |
| condition | enum('new','very_good','good','worn') null | |
| size | varchar(32) null | |
| color | varchar(64) null | color key |
| material | varchar(128) null | |
| price | decimal(10,2) nullable | nullable while in draft |
| allows_trades | boolean default false | |
| allows_offers | boolean default false | |
| shipping_size | enum('S','M','L') nullable | |
| pickup_enabled | boolean null | |
| free_shipping | boolean null | |
| exact_shipping_price | decimal(10,2) null | |
| location | varchar(128) null | city |
| status | enum('draft','pending_review','active','reserved','sold') default 'draft' | `pending_review` when seller has `listings_require_review` flag |
| likes | int default 0 | cached wishlist count |
| view_count | unsigned int default 0 | |
| measurements | json null | { chest, length, shoulder, … } |
| created_at / updated_at | timestamps | |

### product_images
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| product_id | ULID (FK products) | |
| url | varchar(2048) | R2 URL |
| sort_order | int default 0 | |

### product_reports
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| reporter_id | ULID (FK users) | |
| product_id | ULID (FK products) | |
| reason | enum('counterfeit','prohibited','misleading','spam','other') | |
| description | text null | |
| status | enum('pending','reviewed','dismissed') default 'pending' | |
| created_at / updated_at | timestamps | |

### wishlist_items
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID (FK users) | |
| product_id | ULID (FK products) | |
| created_at | timestamp | |
Unique: (user_id, product_id)

### conversations
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| participant_one_id | ULID (FK users) | stored sorted (lower ULID first) to enforce uniqueness |
| participant_two_id | ULID (FK users) | |
| product_id | ULID null (FK products) | first product that started the conversation |
| type | enum('user','admin_support') default 'user' | |
| allow_replies | boolean null | set to false when a user account is deleted |
| status | enum('open','resolved') null | for admin_support conversations |
| last_message_at | timestamp null | |
| created_at / updated_at | timestamps | |
Unique: (participant_one_id, participant_two_id) — one conversation per user pair

### messages
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| conversation_id | ULID (FK conversations) | |
| sender_id | ULID (FK users) | |
| type | enum('text','image','system_inquiry','system_offer','system_order','system_trade','system_status') | |
| body | text null | text content or image URL |
| payload | json null | `{ offerId }` \| `{ orderId }` \| `{ tradeId }` \| `{ productId, text }` |
| read_at | timestamp null | |
| created_at | timestamp | |

### offers
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| product_id | ULID (FK products) | |
| buyer_id | ULID (FK users) | |
| seller_id | ULID (FK users) | |
| offered_price | decimal(10,2) | |
| message | text null | |
| status | enum('pending','accepted','declined','countered','ordered') default 'pending' | `ordered` once the seller accepts and an order is created |
| counter_price | decimal(10,2) null | set when seller counters |
| expires_at | timestamp null | |
| created_at / updated_at | timestamps | |

### trades
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| product_id | ULID (FK products) | seller's item (what buyer wants) |
| offered_product_id | ULID (FK products) | buyer's item (what they offer) |
| buyer_id | ULID (FK users) | |
| seller_id | ULID (FK users) | |
| message | text null | |
| status | enum('active','accepted','declined','countered','completed') default 'active' | |
| created_at / updated_at | timestamps | |

### orders
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| order_number | varchar(32) unique | e.g. "#21346" |
| buyer_id | ULID (FK users) | |
| seller_id | ULID (FK users) | |
| product_id | ULID (FK products) | |
| offer_id | ULID null (FK offers) | set if order came from an accepted offer |
| trade_id | ULID null (FK trades) | set if order came from an accepted trade |
| subtotal | decimal(10,2) | |
| discount | decimal(10,2) default 0 | |
| shipping_cost | decimal(10,2) | |
| total | decimal(10,2) | |
| payment_method | enum('cash','card','bank_transfer','trade') | |
| delivery_method | varchar(64) | |
| status | enum('pending','accepted','shipped','delivered','completed','declined','cancelled') default 'pending' | `cancelled` set automatically when a party requests account deletion during an active order |
| shipping_name | varchar(255) null | |
| shipping_street | varchar(255) null | |
| shipping_city | varchar(128) null | |
| shipping_phone | varchar(32) null | |
| created_at / updated_at | timestamps | |

### reviews
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| order_id | ULID (FK orders) | |
| reviewer_id | ULID (FK users) | who wrote the review |
| reviewed_id | ULID (FK users) | who is being reviewed |
| rating | tinyint (1-5) | |
| comment | text null | |
| role | enum('buyer','seller') | role of the person being reviewed |
| created_at | timestamp | |
Unique: (order_id, reviewer_id) — one review per person per order

### support_inquiries
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID null (FK users) | null if anonymous |
| name | varchar(255) null | for anonymous submissions |
| email | varchar(255) null | for anonymous submissions |
| subject | varchar(255) | |
| body | text | |
| status | enum('open','resolved') default 'open' | |
| created_at / updated_at | timestamps | |

### push_tokens
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID (FK users) | |
| token | varchar(512) | Expo push token |
| platform | enum('ios','android') | |
| created_at / updated_at | timestamps | |
Unique: (user_id, token)

### announcements
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| title | varchar(255) | |
| body | text | |
| target_group | enum('all','verified','city','listings_require_review') default 'all' | |
| target_value | varchar(255) null | e.g. city name when target_group = 'city' |
| created_by | ULID (FK users) | admin who created it |
| sent_at | timestamp null | |
| expires_at | timestamp null | |
| created_at / updated_at | timestamps | |

### announcement_reads
| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK) | |
| announcement_id | ULID (FK announcements) | |
| user_id | ULID (FK users) | |
| read_at | timestamp | |
Unique: (announcement_id, user_id)

### blog_posts
| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK) | |
| title | varchar(255) | |
| slug | varchar(255) unique | |
| tag | varchar(255) | |
| excerpt | text | |
| content | longtext null | legacy HTML content |
| blocks | json null | structured content blocks `[{ type, text?, author?, file?, caption?, url? }]` |
| cover_image | varchar(255) null | |
| cover_color | varchar(20) null | |
| blog_author_id | bigint null (FK blog_authors) | |
| read_time | varchar(20) default '3 min' | |
| is_published | boolean default false | |
| published_at | timestamp null | |
| created_at / updated_at | timestamps | |

### blog_authors
| Column | Type | Notes |
|--------|------|-------|
| id | bigint (PK) | |
| name | varchar(255) | |
| avatar | varchar(2048) null | |
| bio | varchar(255) null | |
| created_at / updated_at | timestamps | |

### campaigns
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| name | varchar(255) | |
| description | text null | |
| channel | enum('instagram','facebook','tiktok','flyer','influencer','other') | |
| status | enum('active','paused','completed') default 'active' | |
| starts_at | date null | |
| ends_at | date null | |
| created_at / updated_at | timestamps | |

### campaign_expenses
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| campaign_id | ULID (FK campaigns) | |
| amount | decimal(10,2) | |
| note | varchar(255) null | |
| spent_at | date | |
| created_at | timestamp | |

### campaign_events
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| campaign_id | ULID (FK campaigns) | |
| type | enum('link_click','app_install') | |
| platform | enum('ios','android','desktop') null | |
| outcome | enum('app_opened','store_redirect') null | |
| created_at | timestamp | |

### share_views
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| entity_type | enum('product','profile') | |
| entity_id | ULID | |
| platform | enum('ios','android','desktop') | |
| outcome | enum('app_opened','store_redirect','unknown') | |
| referrer | varchar(255) null | |
| referrer_platform | enum('instagram','whatsapp','facebook','twitter','direct','other') null | |
| created_at | timestamp | |

### email_verification_tokens
| Column | Type | Notes |
|--------|------|-------|
| email | varchar(255) (PK) | |
| token | varchar(255) | bcrypt-hashed OTP |
| sent_at | timestamp | used for resend rate-limiting (60 s cooldown) |
| created_at | timestamp | used for expiry check (15 min TTL) |

---

## Key Relationships

- User has many Products (as seller)
- User has many Orders (as buyer), many Orders (as seller)
- User has one UserPreferences
- User has many UserAddresses
- User blocks many Users (via user_blocks)
- Product belongs to User (seller), Brand
- Product has many ProductImages
- Product has many ProductReports
- Conversation belongs to two Users; has many Messages
- Offer belongs to Product, buyer User, seller User
- Trade belongs to two Products, buyer User, seller User
- Order belongs to Product, buyer User, seller User; optionally Offer or Trade
- Review belongs to Order, reviewer User, reviewed User
- Announcement has many AnnouncementReads
- Campaign has many CampaignExpenses, CampaignEvents
- Campaign optionally referenced by Users (acquired_via_campaign_id)

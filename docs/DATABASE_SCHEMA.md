# Database Schema

## Tables

### users
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| firebase_uid | varchar(128) unique | Firebase identity reference |
| name | varchar(255) | |
| username | varchar(64) unique | |
| email | varchar(255) unique | |
| avatar | varchar(2048) null | R2 URL |
| location | varchar(128) null | city name |
| bio | text null | |
| phone | varchar(32) null | |
| is_verified | boolean default false | admin-toggled |
| rating | decimal(3,2) default 0 | cached average |
| total_reviews | int default 0 | cached count |
| last_active_at | timestamp null | |
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
| cities | json null | array of city names |

### user_addresses
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| user_id | ULID (FK users) | |
| name | varchar(255) | recipient name |
| street | varchar(255) | |
| city | varchar(128) | |
| phone | varchar(32) | |
| is_default | boolean default false | |

### brands
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| name | varchar(255) | |
| slug | varchar(255) unique | e.g. "zara" |
| logo_url | varchar(2048) null | |
| is_active | boolean default true | |
| sort_order | int default 0 | |

### categories
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| parent_id | ULID null (FK categories) | null = root level |
| name | varchar(255) | display name |
| key | varchar(255) unique | e.g. "women-tops-bluze" |
| icon | varchar(64) null | icon name |
| is_active | boolean default true | |
| sort_order | int default 0 | |

3-level hierarchy: `women/men` → `tops/shoes/etc.` → `Bluze/Trenerke/etc.`

### shipping_options
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| size | enum('S','M','L') | |
| label | varchar(128) | display name |
| price | decimal(8,2) | |
| description | varchar(255) null | |
| is_active | boolean default true | |
| sort_order | int default 0 | |

### products
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| seller_id | ULID (FK users) | |
| brand_id | ULID null (FK brands) | null if custom brand |
| brand_custom | varchar(255) null | free-text brand |
| title | varchar(255) | |
| description | text | |
| root_category | enum('women','men') | |
| category | varchar(128) | section key |
| subcategory | varchar(128) | item key |
| condition | enum('novo','kao_novo','odlican','dobar','zadrzavajuci') | |
| size | varchar(32) | |
| color | varchar(64) | color key |
| material | varchar(128) | |
| price | decimal(10,2) | |
| allows_trades | boolean default false | |
| allows_offers | boolean default false | |
| shipping_size | enum('S','M','L') | |
| location | varchar(128) | city |
| status | enum('draft','active','sold') default 'draft' | |
| likes | int default 0 | cached wishlist count |
| measurements | json null | { chest, length, shoulder, ... } |
| created_at / updated_at | timestamps | |

### product_images
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| product_id | ULID (FK products) | |
| url | varchar(2048) | R2 URL |
| sort_order | int default 0 | |

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
| participant_one_id | ULID (FK users) | always stored as lower ID |
| participant_two_id | ULID (FK users) | |
| product_id | ULID null (FK products) | first product that started the convo |
| last_message_at | timestamp null | |
| created_at / updated_at | timestamps | |
Unique: (participant_one_id, participant_two_id) — one convo per user pair

### messages
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| conversation_id | ULID (FK conversations) | |
| sender_id | ULID (FK users) | |
| type | enum('text','system_inquiry','system_offer','system_order','system_trade','system_status') | |
| body | text null | text content |
| payload | json null | { offerId } \| { orderId } \| { tradeId } \| { productId, text } |
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
| status | enum('pending','accepted','declined','countered') default 'pending' | |
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
| status | enum('active','accepted','declined') default 'active' | |
| created_at / updated_at | timestamps | |

### orders
| Column | Type | Notes |
|--------|------|-------|
| id | ULID (PK) | |
| order_number | varchar(32) unique | e.g. "#21346" |
| buyer_id | ULID (FK users) | |
| seller_id | ULID (FK users) | |
| product_id | ULID (FK products) | |
| offer_id | ULID null (FK offers) | set if order came from accepted offer |
| subtotal | decimal(10,2) | |
| discount | decimal(10,2) default 0 | |
| shipping_cost | decimal(10,2) | |
| total | decimal(10,2) | |
| payment_method | varchar(64) | |
| delivery_method | varchar(64) | |
| status | enum('pending','accepted','shipped','delivered','completed','declined') default 'pending' | |
| shipping_name | varchar(255) | |
| shipping_street | varchar(255) | |
| shipping_city | varchar(128) | |
| shipping_phone | varchar(32) | |
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

## Key Relationships

- User has many Products (as seller)
- User has many Orders (as buyer), many Orders (as seller)
- User has one UserPreferences
- User has many UserAddresses
- Product belongs to User (seller), Brand, Category
- Product has many ProductImages
- Conversation belongs to two Users; has many Messages
- Offer belongs to Product, buyer User, seller User
- Trade belongs to Product (wanted), Product (offered), buyer User, seller User
- Order belongs to Product, buyer User, seller User, optional Offer
- Review belongs to Order, reviewer User, reviewed User

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DevSeeder — mirrors the mock server seed data exactly.
 *
 * Users:     5  (test_korisnik, amira_h, lejla_b, kenan_m, sara_m)
 * Brands:    8
 * Products:  16 (incl. 2 drafts, 2 sold, 1 reserved→active)
 * Wishlist:  8 entries
 * Offers:    2
 * Trades:    1
 * Orders:    4
 * Reviews:   4
 * Convos:    2
 * Messages:  10
 */
class DevSeeder extends Seeder
{
    public function run(): void
    {
        $now     = now();
        $days    = fn (int $n) => $now->copy()->subDays($n)->toDateTimeString();
        $hours   = fn (int $n) => $now->copy()->subHours($n)->toDateTimeString();
        $uid     = fn ()       => (string) Str::ulid();

        // ── IDs ───────────────────────────────────────────────────────────────

        $U_TEST  = $uid(); $U_AMIRA = $uid(); $U_LEJLA = $uid();
        $U_KENAN = $uid(); $U_SARA  = $uid();

        $BR_ZARA    = $uid(); $BR_HM      = $uid(); $BR_MANGO   = $uid();
        $BR_BERSHKA = $uid(); $BR_STRAD   = $uid(); $BR_NIKE    = $uid();
        $BR_ADIDAS  = $uid(); $BR_RL      = $uid();

        $P01 = $uid(); $P02 = $uid(); $P03 = $uid(); $P04 = $uid();
        $P05 = $uid(); $P06 = $uid(); $P07 = $uid(); $P08 = $uid();
        $P09 = $uid(); $P10 = $uid(); $P11 = $uid(); $P12 = $uid();
        $P13 = $uid(); $P14 = $uid(); $P15 = $uid(); $P16 = $uid();

        $C01  = $uid(); $C02  = $uid();
        $OF01 = $uid(); $OF02 = $uid();
        $TR01 = $uid();
        $ORD01 = $uid(); $ORD02 = $uid(); $ORD03 = $uid(); $ORD04 = $uid();
        $REV01 = $uid(); $REV02 = $uid(); $REV03 = $uid(); $REV04 = $uid();

        // ── Users ─────────────────────────────────────────────────────────────

        DB::table('users')->insert([
            [
                'id'                       => $U_TEST,
                'name'                     => 'Test Korisnik',
                'username'                 => 'test_korisnik',
                'email'                    => 'test@tavan.ba',
                'email_verified_at'        => $days(120),
                'password'                 => Hash::make('test123'),
                'avatar'                   => null,
                'location'                 => 'Sarajevo',
                'bio'                      => 'Prodajem odjeću u odličnom stanju. Brza dostava!',
                'phone'                    => '+38761000001',
                'is_verified'              => true,
                'profile_setup_done'       => true,
                'feed_setup_done'          => true,
                'first_listing_coach_seen' => true,
                'first_draft_coach_seen'   => true,
                'notifications_enabled'    => true,
                'rating'                   => 4.90,
                'total_reviews'            => 34,
                'last_active_at'           => $hours(1),
                'created_at'               => $days(120),
                'updated_at'               => $hours(1),
            ],
            [
                'id'                       => $U_AMIRA,
                'name'                     => 'Amira Hodžić',
                'username'                 => 'amira_h',
                'email'                    => 'amira@tavan.ba',
                'email_verified_at'        => $days(90),
                'password'                 => Hash::make('lozinka123'),
                'avatar'                   => null,
                'location'                 => 'Mostar',
                'bio'                      => 'Ljubiteljica mode. Redovno osvježavam garderobu.',
                'phone'                    => '+38761000002',
                'is_verified'              => true,
                'profile_setup_done'       => true,
                'feed_setup_done'          => true,
                'first_listing_coach_seen' => true,
                'first_draft_coach_seen'   => true,
                'notifications_enabled'    => true,
                'rating'                   => 4.70,
                'total_reviews'            => 18,
                'last_active_at'           => $hours(3),
                'created_at'               => $days(90),
                'updated_at'               => $hours(3),
            ],
            [
                'id'                       => $U_LEJLA,
                'name'                     => 'Lejla Begić',
                'username'                 => 'lejla_b',
                'email'                    => 'lejla@tavan.ba',
                'email_verified_at'        => $days(60),
                'password'                 => Hash::make('lozinka123'),
                'avatar'                   => null,
                'location'                 => 'Tuzla',
                'bio'                      => 'Prodajem brendiranu odjeću po povoljnim cijenama.',
                'phone'                    => '+38761000003',
                'is_verified'              => false,
                'profile_setup_done'       => true,
                'feed_setup_done'          => true,
                'first_listing_coach_seen' => true,
                'first_draft_coach_seen'   => true,
                'notifications_enabled'    => true,
                'rating'                   => 4.50,
                'total_reviews'            => 9,
                'last_active_at'           => $days(1),
                'created_at'               => $days(60),
                'updated_at'               => $days(1),
            ],
            [
                'id'                       => $U_KENAN,
                'name'                     => 'Kenan Mahmić',
                'username'                 => 'kenan_m',
                'email'                    => 'kenan@tavan.ba',
                'email_verified_at'        => $days(45),
                'password'                 => Hash::make('lozinka123'),
                'avatar'                   => null,
                'location'                 => 'Sarajevo',
                'bio'                      => null,
                'phone'                    => null,
                'is_verified'              => false,
                'profile_setup_done'       => true,
                'feed_setup_done'          => true,
                'first_listing_coach_seen' => true,
                'first_draft_coach_seen'   => true,
                'notifications_enabled'    => true,
                'rating'                   => 5.00,
                'total_reviews'            => 3,
                'last_active_at'           => $hours(6),
                'created_at'               => $days(45),
                'updated_at'               => $hours(6),
            ],
            [
                'id'                       => $U_SARA,
                'name'                     => 'Sara Milić',
                'username'                 => 'sara_m',
                'email'                    => 'sara@tavan.ba',
                'email_verified_at'        => $days(20),
                'password'                 => Hash::make('lozinka123'),
                'avatar'                   => null,
                'location'                 => 'Banja Luka',
                'bio'                      => null,
                'phone'                    => null,
                'is_verified'              => false,
                'profile_setup_done'       => true,
                'feed_setup_done'          => true,
                'first_listing_coach_seen' => true,
                'first_draft_coach_seen'   => true,
                'notifications_enabled'    => true,
                'rating'                   => 0.00,
                'total_reviews'            => 0,
                'last_active_at'           => $days(2),
                'created_at'               => $days(20),
                'updated_at'               => $days(2),
            ],
        ]);

        // ── User Preferences ──────────────────────────────────────────────────

        DB::table('user_preferences')->insert([
            [
                'id'           => $uid(),
                'user_id'      => $U_TEST,
                'top_sizes'    => json_encode(['M', 'L']),
                'bottom_sizes' => json_encode(['32', '34']),
                'shoe_sizes'   => json_encode(['43', '44']),
                'categories'   => json_encode(['men']),
                'subcategories'=> json_encode(['men-tops', 'men-bottoms', 'men-jackets']),
                'cities'       => json_encode(['Sarajevo', 'Mostar']),
            ],
            [
                'id'           => $uid(),
                'user_id'      => $U_AMIRA,
                'top_sizes'    => json_encode(['S', 'M']),
                'bottom_sizes' => json_encode(['36', '38']),
                'shoe_sizes'   => json_encode(['38', '39']),
                'categories'   => json_encode(['women']),
                'subcategories'=> json_encode(['women-tops', 'women-dresses', 'women-shoes']),
                'cities'       => json_encode(['Mostar']),
            ],
            [
                'id'           => $uid(),
                'user_id'      => $U_KENAN,
                'top_sizes'    => json_encode(['L', 'XL']),
                'bottom_sizes' => json_encode(['34', '36']),
                'shoe_sizes'   => json_encode(['44', '45']),
                'categories'   => json_encode(['men']),
                'subcategories'=> json_encode(['men-tops', 'men-shoes']),
                'cities'       => json_encode(['Sarajevo']),
            ],
        ]);

        // ── Brands ────────────────────────────────────────────────────────────

        DB::table('brands')->insert([
            ['id' => $BR_ZARA,    'name' => 'ZARA',          'slug' => 'zara',          'logo_url' => null, 'is_active' => true, 'sort_order' => 0, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_HM,      'name' => 'H&M',           'slug' => 'hm',            'logo_url' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_MANGO,   'name' => 'Mango',         'slug' => 'mango',         'logo_url' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_BERSHKA, 'name' => 'Bershka',       'slug' => 'bershka',       'logo_url' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_STRAD,   'name' => 'Stradivarius',  'slug' => 'stradivarius',  'logo_url' => null, 'is_active' => true, 'sort_order' => 4, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_NIKE,    'name' => 'Nike',          'slug' => 'nike',          'logo_url' => null, 'is_active' => true, 'sort_order' => 5, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_ADIDAS,  'name' => 'Adidas',        'slug' => 'adidas',        'logo_url' => null, 'is_active' => true, 'sort_order' => 6, 'created_at' => $days(180), 'updated_at' => $days(180)],
            ['id' => $BR_RL,      'name' => 'Ralph Lauren',  'slug' => 'ralph-lauren',  'logo_url' => null, 'is_active' => true, 'sort_order' => 7, 'created_at' => $days(180), 'updated_at' => $days(180)],
        ]);

        // ── Products ─────────────────────────────────────────────────────────
        // Condition map: new→novo, very_good→kao_novo, good→odlican, worn→dobar
        // Status map: reserved→active  (backend has no 'reserved' status)
        // shipping_size is NOT nullable; default 'S' for draft with null

        $products = [
            // ── test_korisnik (U_TEST) ──
            [
                'id'            => $P01,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_NIKE,
                'brand_custom'  => null,
                'title'         => 'Nike Dri-FIT majica kratkih rukava',
                'description'   => 'Nike Dri-FIT majica u odličnom stanju. Nošena svega nekoliko puta. Brzo odvodenje znoja.',
                'root_category' => 'men',
                'category'      => 'tops',
                'subcategory'   => 'Majice kratkih rukava',
                'condition'     => 'kao_novo',
                'size'          => 'M',
                'color'         => 'crna',
                'material'      => 'Poliester',
                'price'         => 25.00,
                'allows_trades' => false,
                'allows_offers' => true,
                'shipping_size' => 'S',
                'location'      => 'Sarajevo',
                'status'        => 'active',
                'likes'         => 7,
                'measurements'  => json_encode([]),
                'created_at'    => $days(30),
                'updated_at'    => $days(30),
            ],
            [
                'id'            => $P02,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_ZARA,
                'brand_custom'  => null,
                'title'         => 'ZARA lanena košulja dugih rukava',
                'description'   => 'Lagana lanena košulja idealna za ljeto. Kao nova, nošena jednom.',
                'root_category' => 'men',
                'category'      => 'tops',
                'subcategory'   => 'Košulje',
                'condition'     => 'novo',
                'size'          => 'L',
                'color'         => 'bijela',
                'material'      => 'Lan',
                'price'         => 35.00,
                'allows_trades' => false,
                'allows_offers' => true,
                'shipping_size' => 'S',
                'location'      => 'Sarajevo',
                'status'        => 'active',
                'likes'         => 12,
                'measurements'  => json_encode(['chest' => '54', 'length' => '78', 'shoulders' => '46']),
                'created_at'    => $days(25),
                'updated_at'    => $days(25),
            ],
            [
                'id'            => $P03,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_ADIDAS,
                'brand_custom'  => null,
                'title'         => 'Adidas muške trenerke',
                'description'   => 'Adidas trenerke u izvrsnom stanju. Malo nošene, bez ikakvih oštećenja. Idealne za trening ili svakodnevno nošenje.',
                'root_category' => 'men',
                'category'      => 'bottoms',
                'subcategory'   => 'Trenerke',
                'condition'     => 'odlican',
                'size'          => 'M',
                'color'         => 'siva',
                'material'      => 'Pamuk',
                'price'         => 38.00,
                'allows_trades' => true,
                'allows_offers' => true,
                'shipping_size' => 'M',
                'location'      => 'Sarajevo',
                'status'        => 'active',
                'likes'         => 15,
                'measurements'  => json_encode(['waist' => '76', 'hips' => '96', 'inseam' => '78']),
                'created_at'    => $days(20),
                'updated_at'    => $days(20),
            ],
            [
                'id'            => $P04,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_HM,
                'brand_custom'  => null,
                'title'         => 'H&M zimska jakna s kapuljačom',
                'description'   => 'Topla zimska jakna iz H&M, XL veličina. Nošena jednu sezonu, bez vidljivih oštećenja.',
                'root_category' => 'men',
                'category'      => 'jackets',
                'subcategory'   => 'Zimske jakne',
                'condition'     => 'odlican',
                'size'          => 'XL',
                'color'         => 'plava',
                'material'      => 'Poliester',
                'price'         => 55.00,
                'allows_trades' => false,
                'allows_offers' => false,
                'shipping_size' => 'M',
                'location'      => 'Sarajevo',
                'status'        => 'active',
                'likes'         => 6,
                'measurements'  => json_encode(['chest' => '60', 'length' => '72', 'shoulders' => '50']),
                'created_at'    => $days(15),
                'updated_at'    => $days(15),
            ],
            [
                'id'            => $P05,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_RL,
                'brand_custom'  => null,
                'title'         => 'Ralph Lauren polo majica',
                'description'   => 'Originalna Ralph Lauren polo majica. Kupljena u Beču. Odlično stanje.',
                'root_category' => 'men',
                'category'      => 'tops',
                'subcategory'   => 'Polo majice',
                'condition'     => 'novo',
                'size'          => 'L',
                'color'         => 'zelena',
                'material'      => 'Pamuk',
                'price'         => 65.00,
                'allows_trades' => true,
                'allows_offers' => true,
                'shipping_size' => 'S',
                'location'      => 'Sarajevo',
                'status'        => 'active',
                'likes'         => 20,
                'measurements'  => json_encode([]),
                'created_at'    => $days(10),
                'updated_at'    => $days(10),
            ],
            [
                'id'            => $P06,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_BERSHKA,
                'brand_custom'  => null,
                'title'         => 'Bershka chino hlače',
                'description'   => 'Chino hlače iz Bershka. Jedva nošene. Razmišljam o prodaji.',
                'root_category' => 'men',
                'category'      => 'bottoms',
                'subcategory'   => 'Hlače',
                'condition'     => 'novo',
                'size'          => '32',
                'color'         => 'bez',
                'material'      => 'Pamuk',
                'price'         => 30.00,
                'allows_trades' => false,
                'allows_offers' => false,
                'shipping_size' => 'S',
                'location'      => 'Sarajevo',
                'status'        => 'draft',
                'likes'         => 0,
                'measurements'  => json_encode(['waist' => '82', 'inseam' => '80']),
                'created_at'    => $days(5),
                'updated_at'    => $days(5),
            ],

            // ── amira_h (U_AMIRA) ──
            [
                'id'            => $P07,
                'seller_id'     => $U_AMIRA,
                'brand_id'      => $BR_MANGO,
                'brand_custom'  => null,
                'title'         => 'Duga haljina',
                'description'   => 'Prekrasna Mango maxi haljina s cvjetnim uzorkom. Idealna za proljetne prilike. Nošena svega jednom.',
                'root_category' => 'women',
                'category'      => 'dresses',
                'subcategory'   => 'Maxi haljine',
                'condition'     => 'novo',
                'size'          => 'S',
                'color'         => 'crvena',
                'material'      => 'Poliester',
                'price'         => 45.00,
                'allows_trades' => false,
                'allows_offers' => true,
                'shipping_size' => 'S',
                'location'      => 'Mostar',
                'status'        => 'active',
                'likes'         => 28,
                'measurements'  => json_encode(['chest' => '82', 'waist' => '64', 'length' => '105']),
                'created_at'    => $days(22),
                'updated_at'    => $days(22),
            ],
            [
                'id'            => $P08,
                'seller_id'     => $U_AMIRA,
                'brand_id'      => $BR_ZARA,
                'brand_custom'  => null,
                'title'         => 'ZARA svilenkasta bluza bez rukava',
                'description'   => 'Elegantna ZARA bluza bez rukava, svilenkaste teksture. Odlično za ured ili izlazak.',
                'root_category' => 'women',
                'category'      => 'tops',
                'subcategory'   => 'Bluze',
                'condition'     => 'kao_novo',
                'size'          => 'M',
                'color'         => 'bijela',
                'material'      => 'Poliester',
                'price'         => 22.00,
                'allows_trades' => true,
                'allows_offers' => false,
                'shipping_size' => 'S',
                'location'      => 'Mostar',
                'status'        => 'active',
                'likes'         => 14,
                'measurements'  => json_encode(['chest' => '86', 'length' => '58']),
                'created_at'    => $days(18),
                'updated_at'    => $days(18),
            ],
            [
                'id'            => $P09,
                'seller_id'     => $U_AMIRA,
                'brand_id'      => $BR_NIKE,
                'brand_custom'  => null,
                'title'         => 'Nike Air Max patike ženske',
                'description'   => 'Nike Air Max ženske patike, veličina 38. Kupljene prošle sezone, nošene rijetko. Bez ogrebotina.',
                'root_category' => 'women',
                'category'      => 'shoes',
                'subcategory'   => 'Niske patike',
                'condition'     => 'odlican',
                'size'          => '38',
                'color'         => 'bijela',
                'material'      => 'Poliester',
                'price'         => 78.00,
                'allows_trades' => false,
                'allows_offers' => true,
                'shipping_size' => 'M',
                'location'      => 'Mostar',
                'status'        => 'active',
                'likes'         => 33,
                'measurements'  => json_encode([]),
                'created_at'    => $days(12),
                'updated_at'    => $days(12),
            ],
            [
                'id'            => $P10,
                'seller_id'     => $U_AMIRA,
                'brand_id'      => $BR_HM,
                'brand_custom'  => null,
                'title'         => 'H&M kaput vune kratki',
                'description'   => 'Klasičan H&M kaput od mješavine vune. Kupljen u Beogradu, nošen jednu sezonu.',
                'root_category' => 'women',
                'category'      => 'jackets',
                'subcategory'   => 'Kaputi',
                'condition'     => 'odlican',
                'size'          => 'S',
                'color'         => 'smeda',
                'material'      => 'Vuna',
                'price'         => 85.00,
                'allows_trades' => false,
                'allows_offers' => false,
                'shipping_size' => 'M',
                'location'      => 'Mostar',
                'status'        => 'sold',
                'likes'         => 11,
                'measurements'  => json_encode(['chest' => '86', 'length' => '68', 'shoulders' => '38']),
                'created_at'    => $days(50),
                'updated_at'    => $days(8),
            ],

            // ── lejla_b (U_LEJLA) ──
            [
                'id'            => $P11,
                'seller_id'     => $U_LEJLA,
                'brand_id'      => $BR_BERSHKA,
                'brand_custom'  => null,
                'title'         => 'Bershka crop top bez rukava',
                'description'   => 'Trendovski Bershka crop top, kao nov. Nošen jednom.',
                'root_category' => 'women',
                'category'      => 'tops',
                'subcategory'   => 'Majice bez rukava',
                'condition'     => 'novo',
                'size'          => 'XS',
                'color'         => 'crna',
                'material'      => 'Pamuk',
                'price'         => 12.00,
                'allows_trades' => false,
                'allows_offers' => true,
                'shipping_size' => 'S',
                'location'      => 'Tuzla',
                'status'        => 'active',
                'likes'         => 9,
                'measurements'  => json_encode([]),
                'created_at'    => $days(16),
                'updated_at'    => $days(16),
            ],
            [
                'id'            => $P12,
                'seller_id'     => $U_LEJLA,
                'brand_id'      => $BR_STRAD,
                'brand_custom'  => null,
                'title'         => 'Stradivarius skinny farmerke',
                'description'   => 'Stradivarius farmerke, veličina 36. Malo nošene, bez oštećenja.',
                'root_category' => 'women',
                'category'      => 'bottoms',
                'subcategory'   => 'Farmerke',
                'condition'     => 'odlican',
                'size'          => '36',
                'color'         => 'plava',
                'material'      => 'Denim',
                'price'         => 28.00,
                'allows_trades' => false,
                'allows_offers' => true,
                'shipping_size' => 'S',
                'location'      => 'Tuzla',
                'status'        => 'active',
                'likes'         => 17,
                'measurements'  => json_encode(['waist' => '70', 'hips' => '90', 'inseam' => '72']),
                'created_at'    => $days(14),
                'updated_at'    => $days(14),
            ],
            [
                'id'            => $P13,
                'seller_id'     => $U_LEJLA,
                'brand_id'      => $BR_ADIDAS,
                'brand_custom'  => null,
                'title'         => 'Adidas ženske patike Response',
                'description'   => 'Adidas Response trčaće patike, veličina 39. U rezervaciji.',
                'root_category' => 'women',
                'category'      => 'shoes',
                'subcategory'   => 'Niske patike',
                'condition'     => 'odlican',
                'size'          => '39',
                'color'         => 'bijela',
                'material'      => 'Poliester',
                'price'         => 55.00,
                'allows_trades' => false,
                'allows_offers' => false,
                'shipping_size' => 'M',
                'location'      => 'Tuzla',
                'status'        => 'active', // mock had 'reserved'; backend has no such status
                'likes'         => 8,
                'measurements'  => json_encode([]),
                'created_at'    => $days(11),
                'updated_at'    => $days(3),
            ],
            [
                'id'            => $P14,
                'seller_id'     => $U_LEJLA,
                'brand_id'      => $BR_MANGO,
                'brand_custom'  => null,
                'title'         => 'Mango vesta zimska',
                'description'   => 'Topla Mango vesta s unutarnjom podstavom. Idealna za prijelazne periode.',
                'root_category' => 'women',
                'category'      => 'jackets',
                'subcategory'   => 'Prsluci',
                'condition'     => 'novo',
                'size'          => 'M',
                'color'         => 'zelena',
                'material'      => 'Poliester',
                'price'         => 40.00,
                'allows_trades' => true,
                'allows_offers' => true,
                'shipping_size' => 'M',
                'location'      => 'Tuzla',
                'status'        => 'active',
                'likes'         => 5,
                'measurements'  => json_encode([]),
                'created_at'    => $days(8),
                'updated_at'    => $days(8),
            ],
            [
                'id'            => $P15,
                'seller_id'     => $U_LEJLA,
                'brand_id'      => $BR_NIKE,
                'brand_custom'  => null,
                'title'         => 'Nike djevojačka dukserica',
                'description'   => 'Nike dukserica S veličine, skoro nova.',
                'root_category' => 'women',
                'category'      => 'tops',
                'subcategory'   => 'Dukserice',
                'condition'     => 'novo',
                'size'          => 'S',
                'color'         => 'siva',
                'material'      => 'Pamuk',
                'price'         => 32.00,
                'allows_trades' => false,
                'allows_offers' => false,
                'shipping_size' => 'S',
                'location'      => 'Tuzla',
                'status'        => 'sold',
                'likes'         => 22,
                'measurements'  => json_encode([]),
                'created_at'    => $days(40),
                'updated_at'    => $days(12),
            ],
            [
                'id'            => $P16,
                'seller_id'     => $U_TEST,
                'brand_id'      => $BR_ADIDAS,
                'brand_custom'  => null,
                'title'         => 'Adidas originals jakna — draft',
                'description'   => '',
                'root_category' => 'men',
                'category'      => 'jackets',
                'subcategory'   => 'Bomber jakne',
                'condition'     => null,
                'size'          => null,
                'color'         => null,
                'material'      => null,
                'price'         => 0.00,
                'allows_trades' => false,
                'allows_offers' => false,
                'shipping_size' => 'S', // required column; default for drafts
                'location'      => 'Sarajevo',
                'status'        => 'draft',
                'likes'         => 0,
                'measurements'  => json_encode([]),
                'created_at'    => $days(2),
                'updated_at'    => $days(2),
            ],
        ];

        DB::table('products')->insert($products);

        // ── Product Images ────────────────────────────────────────────────────

        $imageRows = [];
        $productImages = [
            $P01 => ['https://images1.vinted.net/t/05_024b3_xEMLLXjzKBfmYhwkFnAatGW8/f800/1775741599.webp?s=3bc590ce92c01819d3b5cd9d19fe37eb94e316dc'],
            $P02 => [
                'https://images1.vinted.net/t/03_00853_UcJC6RAMtp7C1YRCqt8gKpBe/f800/1776018069.webp?s=b8f748cdba3a787ae02be3d093799559edf65ce2',
                'https://images1.vinted.net/t/05_011f7_xsM6ukvzpiPRPaDPqEsoQc5M/f800/1776018070.webp?s=cf13260f816e58f15cf10d3733b339bd58b92065',
                'https://images1.vinted.net/t/06_0091f_DPt6oCcxGN6HkJMbmpWLnmeY/f800/1776018070.webp?s=39e793c6ec6786a32b3ad1b7595335893c6fa7c1',
            ],
            $P03 => [
                'https://images1.vinted.net/t/01_020c4_Fw7pKuKDBsT1niuCG7Y5GuZv/f800/1776018249.webp?s=4973692f923e88d5f21046e3b7168055d034643a',
                'https://images1.vinted.net/t/04_016ce_4ZofPeobNDCxGczGEw6WNhU7/f800/1776018249.webp?s=f2b21142e165beb97c07f84c4b1fffb6c9b4aa9e',
                'https://images1.vinted.net/t/05_003d7_G784J1yQRhmB6fpDyn7TeZdm/f800/1776018249.webp?s=225d8b46d8727cf204d2e6433b418a0f0e319281',
            ],
            $P04 => [
                'https://images1.vinted.net/t/01_0252c_Knzix8gBxYb2dXD6AKDsHeBa/f800/1776018239.webp?s=676bbeda53b4fe7f56f184a4438279328edc5217',
                'https://images1.vinted.net/t/01_016b5_wvYSZFY3Tx385DUESsqusBou/f800/1776018239.webp?s=f25fc0aec8769edc43c116a915db69f67884042c',
                'https://images1.vinted.net/t/06_008f7_4E9Y1uB7S9toLzm1sX4xx2jx/f800/1776018239.webp?s=7d2e7dae72f88745584986396e9cc2d3c0f66c3a',
            ],
            $P05 => [
                'https://images1.vinted.net/t/06_0204a_cBszZSYJYt7FkhVZmMYmuA67/f800/1776018146.webp?s=b14051efec9bc8501edb114d36031629495f9ca4',
                'https://images1.vinted.net/t/05_026fd_afiMdLY7jSqVythWFKRPJ6ZY/f800/1776018146.webp?s=76c2994485171b49898d44f41aaa148dab326168',
                'https://images1.vinted.net/t/06_00cd1_EjwLcy2JqWdKHXzZvZ934F5C/f800/1776018146.webp?s=d83d0c336dffda4382912a120345c262af1b736c',
            ],
            $P06 => [
                'https://images1.vinted.net/t/06_001b4_BscL5PwczKH1yDU7qXd2ZGNd/f800/1775873147.webp?s=f4a2c8107a84416656ef8b20ec5f49e1ecbeb05d',
                'https://images1.vinted.net/t/06_01459_tNG7bKFS5Jmvv9tYcAWhqURx/f800/1775873147.webp?s=d8a6dd5908562b6fdab20a35b2eddf0094a88446',
                'https://images1.vinted.net/t/06_005db_yvbKNgtjNRRuS49AyBr9LXKE/f800/1775873147.webp?s=6ceb7b26eab2e2d9ac67044dccfeaa32bd32325b',
            ],
            $P07 => [
                'https://images1.vinted.net/t/06_00207_TAAbqUsruFrxhbSWRw1rPfVj/f800/1776017628.webp?s=65ba04b3b873d7d152702bc6a7d8e691244cdaac',
                'https://images1.vinted.net/t/06_00d9a_nRTL1Sm74HjdfaCHCZsgySAr/f800/1776017629.webp?s=3dac8aea2095891ede20ae334bdc9cf67caec6ae',
                'https://images1.vinted.net/t/05_02682_VGBAUXjG7tA5Yuot5smmozjt/f800/1776017629.webp?s=3ee928a2809f30913ac52654ccd9473783899a35',
            ],
            $P08 => [
                'https://images1.vinted.net/t/05_002f5_piAus7LnmBYQk6VTpiLdaz1t/f800/1776018127.webp?s=ad9ece602fd027bc73922800e754d2b4a62c3b64',
                'https://images1.vinted.net/t/01_01396_WL9ysaRRWNhnUnPBaozJSk6h/f800/1776018127.webp?s=ddd7c6452dac94fcb7e731dedbe3b9ab41b90655',
                'https://images1.vinted.net/t/05_019fc_LHxpuYP6jM4nb3RBk8grZPAY/f800/1776018127.webp?s=cad7245ab20b2cd1cba693d3105261498703f922',
            ],
            $P09 => [
                'https://images1.vinted.net/t/04_01bc2_ryD2tYTADbvbPmGd9PmvTt9G/f800/1775998656.webp?s=bcc42cb753b0ad8df30500f1f46388436625ece7',
                'https://images1.vinted.net/t/03_01ccb_YCqAvCjHtCoQ5KqiY577qkav/f800/1775998656.webp?s=6704166892a7ad6cfdc1371835cc2208159505bd',
                'https://images1.vinted.net/t/05_014b2_y2BHJED6bFP7SbDac9wZwqkV/f800/1775998656.webp?s=e63fb555031c8576f32712dd248f166acab127a7',
            ],
            $P10 => [
                'https://images1.vinted.net/t/06_0186a_DiCyn4LL7vMdR4Ch51CCjFCB/f800/1776010429.webp?s=47fe4a394ed1603241202964788b9c33cd0e6ac4',
                'https://images1.vinted.net/t/05_007a5_ywpQN9e7hZGVfuMemwaJNS3m/f800/1776010429.webp?s=be335ba762992c388e05e67c0adab8fbcdcbfad0',
                'https://images1.vinted.net/t/06_0186e_HYhE237Mrp7R4hUVE7SFEGRa/f800/1776010429.webp?s=156edd724b61d1d10d470bd7cb4350daa183a451',
            ],
            $P11 => [
                'https://images1.vinted.net/t/01_0028b_qCdtv3WAsDkiSCVkvLS3W96T/f800/1776018037.webp?s=453065a554cc1f907781d13b3f1d37f6056597ec',
                'https://images1.vinted.net/t/06_0003c_2dpcxYKhJedz4wnGJ9Sy2MYS/f800/1776018037.webp?s=0dc1d8f9e6f6c58631a338f45fbec5b6cb5609c2',
                'https://images1.vinted.net/t/05_013b5_r8d43ftjjwDmvcZJCcZTKkhA/f800/1776018038.webp?s=2d642685e096bfebf142daf15b2581b6e0a2e8ab',
            ],
            $P12 => [], // no images in mock
            $P13 => [
                'https://images1.vinted.net/t/06_01745_7ZBnrVqFSsfoyr1mRQvC62WV/f800/1776017883.webp?s=60b66ebd89cf7a0c84c57b75b8e9341c570a1864',
                'https://images1.vinted.net/t/05_0137a_rLxSnDFRki7BTwzMktmDRygL/f800/1776017883.webp?s=32b0061a5d90d633767dbfdfb53e1a03fb3ecfb9',
                'https://images1.vinted.net/t/04_019b2_stCUQLSgZJ5shjDAN9cNHWqA/f800/1776017883.webp?s=2b962fc331ba65d1ffd3c970503eb42867510348',
            ],
            $P14 => ['https://images1.vinted.net/t/06_02628_i4drJmo4SHTic4TZgUNQnuGx/f800/1776018663.webp?s=f46db13203791bf29156daf9510e5bb33e653522'],
            $P15 => [
                'https://images1.vinted.net/t/01_013c2_9xPRiUta461QdwRrjCWnJq3Y/f800/1776018304.webp?s=55564085f676dee6f9775499df72fdd2fc3c4605',
                'https://images1.vinted.net/t/05_0072b_BiJdpDZi5yBMBDXsrbWX5qP5/f800/1776018304.webp?s=181dbc7b915866d6c0f492033ad8ade235615dbd',
                'https://images1.vinted.net/t/05_00fad_nrGqyJDAm8cR8KaWim1FQd9b/f800/1776018304.webp?s=0f984f0caec7795f8b6401d8f2eeaed92b135992',
            ],
            $P16 => [
                'https://images1.vinted.net/t/06_0179d_6PWA8sXmQEHRs3y4oL72C75f/f800/1776017999.webp?s=b8f64ad38a8e6e9b0bef457ff2d10c12a7b70bc1',
                'https://images1.vinted.net/t/05_018f2_DaafBrdYDCnqQhPpDrukVEeh/f800/1776017999.webp?s=f8fe2e248257d62a98a1652b3f9505b70e830a64',
            ],
        ];

        foreach ($productImages as $productId => $urls) {
            foreach ($urls as $sort => $url) {
                $imageRows[] = [
                    'id'         => $uid(),
                    'product_id' => $productId,
                    'url'        => $url,
                    'sort_order' => $sort,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }

        if (!empty($imageRows)) {
            DB::table('product_images')->insert($imageRows);
        }

        // ── Wishlist ──────────────────────────────────────────────────────────

        DB::table('wishlist_items')->insert([
            ['id' => $uid(), 'user_id' => $U_TEST,  'product_id' => $P07, 'created_at' => $days(10)],
            ['id' => $uid(), 'user_id' => $U_TEST,  'product_id' => $P08, 'created_at' => $days(8)],
            ['id' => $uid(), 'user_id' => $U_TEST,  'product_id' => $P12, 'created_at' => $days(5)],
            ['id' => $uid(), 'user_id' => $U_KENAN, 'product_id' => $P03, 'created_at' => $days(12)],
            ['id' => $uid(), 'user_id' => $U_KENAN, 'product_id' => $P05, 'created_at' => $days(7)],
            ['id' => $uid(), 'user_id' => $U_SARA,  'product_id' => $P07, 'created_at' => $days(6)],
            ['id' => $uid(), 'user_id' => $U_SARA,  'product_id' => $P09, 'created_at' => $days(4)],
            ['id' => $uid(), 'user_id' => $U_SARA,  'product_id' => $P11, 'created_at' => $days(2)],
        ]);

        // ── Offers ────────────────────────────────────────────────────────────

        DB::table('offers')->insert([
            [
                'id'            => $OF01,
                'product_id'    => $P03,
                'buyer_id'      => $U_KENAN,
                'seller_id'     => $U_TEST,
                'offered_price' => 30.00,
                'message'       => 'Hej, bi li prihvatio 30 KM? Mogu preuzeti odmah.',
                'status'        => 'countered',
                'counter_price' => 35.00,
                'expires_at'    => null,
                'created_at'    => $days(14),
                'updated_at'    => $days(13),
            ],
            [
                'id'            => $OF02,
                'product_id'    => $P07,
                'buyer_id'      => $U_TEST,
                'seller_id'     => $U_AMIRA,
                'offered_price' => 38.00,
                'message'       => 'Lijepa haljina! Može li za 38 KM?',
                'status'        => 'accepted',
                'counter_price' => null,
                'expires_at'    => null,
                'created_at'    => $days(20),
                'updated_at'    => $days(19),
            ],
        ]);

        // ── Trades ────────────────────────────────────────────────────────────

        DB::table('trades')->insert([
            [
                'id'                 => $TR01,
                'product_id'         => $P08,  // Amira's ZARA bluza (seller item)
                'offered_product_id' => $P01,  // Test user's Nike majica (buyer item)
                'buyer_id'           => $U_TEST,
                'seller_id'          => $U_AMIRA,
                'message'            => 'Nudim svoju Nike majicu za tvoju ZARA bluzu. Oboje M veličina.',
                'status'             => 'active',
                'created_at'         => $days(5),
                'updated_at'         => $days(5),
            ],
        ]);

        // ── Orders ────────────────────────────────────────────────────────────

        DB::table('orders')->insert([
            [
                'id'              => $ORD01,
                'order_number'    => '#10023',
                'buyer_id'        => $U_KENAN,
                'seller_id'       => $U_TEST,
                'product_id'      => $P03,
                'offer_id'        => null,
                'subtotal'        => 38.00,
                'discount'        => 0.00,
                'shipping_cost'   => 5.00,
                'total'           => 43.00,
                'payment_method'  => 'Gotovina pri preuzimanju',
                'delivery_method' => 'Kurirska služba',
                'status'          => 'shipped',
                'shipping_name'   => 'Kenan Mahmić',
                'shipping_street' => 'Titova 12',
                'shipping_city'   => 'Sarajevo',
                'shipping_phone'  => '+38761555001',
                'created_at'      => $days(13),
                'updated_at'      => $days(11),
            ],
            [
                'id'              => $ORD02,
                'order_number'    => '#10019',
                'buyer_id'        => $U_TEST,
                'seller_id'       => $U_AMIRA,
                'product_id'      => $P07,
                'offer_id'        => $OF02,
                'subtotal'        => 38.00,
                'discount'        => 7.00,
                'shipping_cost'   => 5.00,
                'total'           => 36.00,
                'payment_method'  => 'Debit kartica',
                'delivery_method' => 'Kurirska služba',
                'status'          => 'completed',
                'shipping_name'   => 'Test Korisnik',
                'shipping_street' => 'Ferhadija 8',
                'shipping_city'   => 'Sarajevo',
                'shipping_phone'  => '+38761000001',
                'created_at'      => $days(18),
                'updated_at'      => $days(14),
            ],
            [
                'id'              => $ORD03,
                'order_number'    => '#10031',
                'buyer_id'        => $U_SARA,
                'seller_id'       => $U_AMIRA,
                'product_id'      => $P09,
                'offer_id'        => null,
                'subtotal'        => 78.00,
                'discount'        => 0.00,
                'shipping_cost'   => 8.00,
                'total'           => 86.00,
                'payment_method'  => 'Gotovina pri preuzimanju',
                'delivery_method' => 'Kurirska služba',
                'status'          => 'pending',
                'shipping_name'   => 'Sara Milić',
                'shipping_street' => 'Kralja Petra I 44',
                'shipping_city'   => 'Banja Luka',
                'shipping_phone'  => '+38765555002',
                'created_at'      => $days(2),
                'updated_at'      => $days(2),
            ],
            [
                'id'              => $ORD04,
                'order_number'    => '#10008',
                'buyer_id'        => $U_KENAN,
                'seller_id'       => $U_AMIRA,
                'product_id'      => $P10,
                'offer_id'        => null,
                'subtotal'        => 85.00,
                'discount'        => 0.00,
                'shipping_cost'   => 8.00,
                'total'           => 93.00,
                'payment_method'  => 'Debit kartica',
                'delivery_method' => 'Kurirska služba',
                'status'          => 'completed',
                'shipping_name'   => 'Kenan Mahmić',
                'shipping_street' => 'Titova 12',
                'shipping_city'   => 'Sarajevo',
                'shipping_phone'  => '+38761555001',
                'created_at'      => $days(55),
                'updated_at'      => $days(40),
            ],
        ]);

        // ── Reviews ───────────────────────────────────────────────────────────

        DB::table('reviews')->insert([
            [
                'id'          => $REV01,
                'order_id'    => $ORD02,
                'reviewer_id' => $U_TEST,
                'reviewed_id' => $U_AMIRA,
                'rating'      => 5,
                'comment'     => 'Odlična prodavačica! Haljina je tačno kao na opisu, brza dostava.',
                'role'        => 'seller',
                'created_at'  => $days(13),
            ],
            [
                'id'          => $REV02,
                'order_id'    => $ORD02,
                'reviewer_id' => $U_AMIRA,
                'reviewed_id' => $U_TEST,
                'rating'      => 5,
                'comment'     => 'Odličan kupac, plaćanje odmah, preporučujem!',
                'role'        => 'buyer',
                'created_at'  => $days(13),
            ],
            [
                'id'          => $REV03,
                'order_id'    => $ORD04,
                'reviewer_id' => $U_KENAN,
                'reviewed_id' => $U_AMIRA,
                'rating'      => 5,
                'comment'     => 'Kaput je savršen, prodavačica odlična!',
                'role'        => 'seller',
                'created_at'  => $days(38),
            ],
            [
                'id'          => $REV04,
                'order_id'    => $ORD04,
                'reviewer_id' => $U_AMIRA,
                'reviewed_id' => $U_KENAN,
                'rating'      => 5,
                'comment'     => 'Brza uplata, fer kupac.',
                'role'        => 'buyer',
                'created_at'  => $days(38),
            ],
        ]);

        // ── Conversations ─────────────────────────────────────────────────────
        // participant_one_id / participant_two_id must be stored in a consistent
        // order to satisfy the unique constraint; we sort them lexicographically.

        [$p1c01, $p2c01] = $U_TEST < $U_KENAN ? [$U_TEST, $U_KENAN] : [$U_KENAN, $U_TEST];
        [$p1c02, $p2c02] = $U_TEST < $U_AMIRA ? [$U_TEST, $U_AMIRA] : [$U_AMIRA, $U_TEST];

        DB::table('conversations')->insert([
            [
                'id'                  => $C01,
                'participant_one_id'  => $p1c01,
                'participant_two_id'  => $p2c01,
                'product_id'          => $P03,
                'last_message_at'     => $days(11),
                'created_at'          => $days(14),
                'updated_at'          => $days(11),
            ],
            [
                'id'                  => $C02,
                'participant_one_id'  => $p1c02,
                'participant_two_id'  => $p2c02,
                'product_id'          => $P08,
                'last_message_at'     => $days(5),
                'created_at'          => $days(6),
                'updated_at'          => $days(5),
            ],
        ]);

        // ── Messages ──────────────────────────────────────────────────────────

        DB::table('messages')->insert([
            // ── Conversation 1: Kenan & Test user — offer → order flow ──
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_KENAN,
                'type'            => 'system_inquiry',
                'body'            => null,
                'payload'         => json_encode(['product_id' => $P03, 'text' => 'Pozdrav! Jesu li trenerke još dostupne?']),
                'read_at'         => $days(13),
                'created_at'      => $days(14),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_TEST,
                'type'            => 'text',
                'body'            => 'Da, dostupne su! Odličnom stanju, nošene svega nekoliko puta.',
                'payload'         => null,
                'read_at'         => $days(13),
                'created_at'      => $hours(14 * 24 - 2),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_KENAN,
                'type'            => 'system_offer',
                'body'            => null,
                'payload'         => json_encode(['offer_id' => $OF01]),
                'read_at'         => $days(13),
                'created_at'      => $days(14),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_TEST,
                'type'            => 'text',
                'body'            => 'Hvala na ponudi! Minimalna cijena mi je 35 KM zbog originalnih etiketa.',
                'payload'         => null,
                'read_at'         => $days(12),
                'created_at'      => $days(13),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_KENAN,
                'type'            => 'text',
                'body'            => 'Važi, slažem se! Kako plaćamo?',
                'payload'         => null,
                'read_at'         => $days(12),
                'created_at'      => $days(13),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_KENAN,
                'type'            => 'system_order',
                'body'            => null,
                'payload'         => json_encode(['order_id' => $ORD01]),
                'read_at'         => $days(12),
                'created_at'      => $days(13),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C01,
                'sender_id'       => $U_TEST,
                'type'            => 'system_status',
                'body'            => 'Narudžba je poslana kurirskom službom.',
                'payload'         => null,
                'read_at'         => $days(10),
                'created_at'      => $days(11),
            ],

            // ── Conversation 2: Test user & Amira — trade proposal ──
            [
                'id'              => $uid(),
                'conversation_id' => $C02,
                'sender_id'       => $U_TEST,
                'type'            => 'system_inquiry',
                'body'            => null,
                'payload'         => json_encode(['product_id' => $P08, 'text' => 'Hej Amira! Zanima me tvoja ZARA bluza.']),
                'read_at'         => $days(5),
                'created_at'      => $days(6),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C02,
                'sender_id'       => $U_AMIRA,
                'type'            => 'text',
                'body'            => 'Bok! Da, bluza je dostupna, kao nova je.',
                'payload'         => null,
                'read_at'         => $days(5),
                'created_at'      => $days(6),
            ],
            [
                'id'              => $uid(),
                'conversation_id' => $C02,
                'sender_id'       => $U_TEST,
                'type'            => 'system_trade',
                'body'            => null,
                'payload'         => json_encode(['trade_id' => $TR01]),
                'read_at'         => null,
                'created_at'      => $days(5),
            ],
        ]);

        $this->command->info('Dev data seeded: 5 users, 8 brands, 16 products, 2 conversations, 4 orders.');
        $this->command->info('Login: test@tavan.ba / test123');
    }
}

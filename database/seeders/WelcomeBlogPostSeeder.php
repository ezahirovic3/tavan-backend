<?php

namespace Database\Seeders;

use App\Models\BlogAuthor;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WelcomeBlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = BlogAuthor::firstOrCreate(
            ['name' => 'Edib Zahirović'],
            ['bio' => 'CEO i suosnivač Tavana']
        );

        // Note: cover_image intentionally omitted — to be uploaded via admin panel.
        // Re-running this seeder will not overwrite a cover_image set via admin.
        BlogPost::updateOrCreate(
            ['slug' => 'dobrodosli-na-tavan'],
            [
                'title'          => 'Dobrodošli na Tavan — priča koja je počela u kafeu',
                'tag'            => 'brand story',
                'excerpt'        => 'Secondhand moda mijenja pravila igre. Evo kako je razgovor trojice prijatelja u kafeu prerastao u platformu koja mijenja način na koji Bosna kupuje i trguje odjećom.',
                'cover_color'    => '#FB5C90',
                'read_time'      => '5 min',
                'blog_author_id' => $author->id,
                'is_published'   => true,
                'published_at'   => Carbon::parse('2026-05-24 10:00:00'),
                'blocks'         => $this->blocks(),
            ]
        );

        $this->command->info('Seeded welcome blog post.');
    }

    private function blocks(): array
    {
        return [
            // Cold open
            ['type' => 'paragraph', 'text' => 'Bio je novembar 2025. Sjedili smo u kafeu. Minet je pomenuo sestru — tinejdžerka, pun ormar odjeće, ne zna gdje da je proda ili proslijedi. Zvuči poznato? Iz tog razgovora se rodila ideja koja je danas postala Tavan.'],

            // Trend context
            ['type' => 'heading', 'text' => 'Secondhand više nije prošlost'],
            ['type' => 'paragraph', 'text' => 'Nešto se tiho mijenja u načinu na koji se oblačimo. Secondhand više nije sinonim za nuždu ili kompromis — postalo je svjestan izbor. Mladi kupuju vintage komade jer su jedinstveni. Prodaju odjeću iz ormara jer znaju da netko drugi to jedva čeka. Trampe umjesto bacanja. Svjesnost umjesto brzine.'],
            ['type' => 'paragraph', 'text' => 'Thrift kultura je ovdje, i ostaje.'],

            // Industry validation
            ['type' => 'heading', 'text' => 'Globalni signal koji sve potvrđuje'],
            ['type' => 'paragraph', 'text' => '18. februara 2026. godine, eBay je kupio Depop — jednu od najvećih secondhand platformi na svijetu — za 1,2 milijarde dolara. Kada investicijski giganti plaćaju toliko za resale platforme, to govori sve što treba znati o smjeru u kom se kreće moda.'],

            // Local gap
            ['type' => 'heading', 'text' => 'A Bosna?'],
            ['type' => 'paragraph', 'text' => 'Ako živite u Bosni i Hercegovini i pokušate prodati odjeću ili pronaći jedinstven komad, opcije su vam limitirane. Instagram preprodavci, Facebook grupe, ponešto raštrkano po marketplace stranicama — sve bez strukture, bez sigurnosti, bez filtera.'],
            ['type' => 'paragraph', 'text' => 'Tržište postoji. Platforma nije postojala.'],

            // Name meaning
            ['type' => 'heading', 'text' => 'Zašto baš Tavan?'],
            ['type' => 'paragraph', 'text' => 'Tavan je mjesto gdje čuvamo stvari koje volimo, ali više ne koristimo. Stari džemper, vintage jakna, cipele koje su bile savršene — sve čeka negdje u kutiji ili ormaru.'],
            ['type' => 'paragraph', 'text' => 'Ali "Tavan" je i igra riječima — "ta van" — taj komad je van, dostupan, čeka svog novog vlasnika.'],
            ['type' => 'paragraph', 'text' => 'Dva značenja. Jedna platforma.'],

            // What Tavan is + vision
            ['type' => 'heading', 'text' => 'Šta je Tavan?'],
            ['type' => 'paragraph', 'text' => 'Tavan je aplikacija kojoj se okrenete kada tražite nešto posebno za ormar. Kvalitetni komadi, u odličnom stanju, po cijeni koja ima smisla. Otvorite Tavan kada vam treba nešto novo za nositi — kao što otvorite OLX kada vam nešto treba.'],
            ['type' => 'paragraph', 'text' => 'Ali dugoročna vizija ide korak dalje. Tavan treba postati vaš online ormar — dođete do komada koji volite, nosite ga, i kada ste ga "završili", proslijedite ga dalje nekome drugome. Istraživanja pokazuju da većina žena neki komad obuče svega 4–5 puta prije nego što završi zaboravljen u ormaru. Tavan tu naviku želi promijeniti: svaki komad zaslužuje više od pet izlazaka.'],

            // Timeline
            ['type' => 'heading', 'text' => 'Naš put do danas'],

            ['type' => 'subheading', 'text' => 'Novembar 2025 — iskra'],
            ['type' => 'paragraph', 'text' => 'Razgovor u kafeu koji je sve pokrenuo. Nas trojica — Minet, Safet i ja — odlučujemo da ideja ne ostane samo ideja. Minet smišlja i ime.'],

            ['type' => 'subheading', 'text' => 'Januar 2026 — projekt dobija formu'],
            ['type' => 'paragraph', 'text' => 'Nova godina, novi projekat. Safet preuzima dizajn i gradi vizualni identitet Tavana od nule. Aplikacija počinje dobijati oblik.'],

            ['type' => 'subheading', 'text' => '18. februar 2026 — vanjska potvrda'],
            ['type' => 'paragraph', 'text' => 'Vijest o eBay/Depop akviziciji stiže kao vjetar u leđa. Nismo jedini koji vjeruju u ovaj smjer.'],

            ['type' => 'subheading', 'text' => '1. april 2026 — Tavan izlazi van'],
            ['type' => 'paragraph', 'text' => 'Pokrenuli smo društvene mreže i prvi put pokazali Tavan javnosti. Crno-bijelo, minimalistično — Safetov potpis.'],
            ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DWl3tKNjI1I/'],

            ['type' => 'subheading', 'text' => 'April / maj 2026 — beta testiranje'],
            ['type' => 'paragraph', 'text' => 'Pozvali smo prve korisnike da isprobaju aplikaciju. Bez plaćenog marketinga, bez boosted objava — završili smo s 18 beta testera koji su aktivno koristili platformu. Za ovaj korak razvoja, to je ogroman broj.'],
            ['type' => 'paragraph', 'text' => 'Slušali smo svaki komentar. Prilagodili smo ekrane, dodali kategorije, izmijenili ponašanja. Čak je i vizualni identitet evoluirao — aplikacija je krenula kao potpuno crno-bijela, a na osnovu povratnih informacija dodali smo akcentnu boju koja je danas dio i aplikacije i našeg vizualnog identiteta.'],
            ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DYPt0FlMqXi/'],

            // CTA
            ['type' => 'heading', 'text' => 'Lansiranje je za par dana'],
            ['type' => 'paragraph', 'text' => 'Nas trojica — Minet, Safet i ja. Jedna ideja iz kafea. I aplikacija koja je za nekoliko dana dostupna svima.'],
            ['type' => 'paragraph', 'text' => 'Najavu lansiranja objavljujemo preko Instagrama. Pratite @tavan.store da budete među prvima koji uđu u Tavan kada se vrata otvore.'],
        ];
    }
}

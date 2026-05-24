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
                'title' => 'Jedna kafa kasnije, nastao je Tavan',
                'tag' => 'brand story',
                'excerpt' => 'Priča o tome kako su kafa, pun ormar i jedna dobra ideja završili kao Tavan.',
                'cover_color' => '#FB5C90',
                'read_time' => '5 min',
                'blog_author_id' => $author->id,
                'is_published' => true,
                'published_at' => Carbon::parse('2026-05-24 10:00:00'),
                'blocks' => $this->blocks(),
            ]
        );

        $this->command->info('Seeded welcome blog post.');
    }

    private function blocks(): array
    {
        return [

            // Cold open
            [
                'type' => 'paragraph',
                'text' => 'Bio je novembar 2025. Sjedili smo na kafi kada je Minet spomenuo sestru — pun ormar odjeće, a pola toga više ne nosi. Sve dobri komadi, ali jednostavno “dosadni”. Zvuči poznato? Iz tog razgovora nastala je ideja koja je danas postala Tavan.'

            ],

            // Trend context
            [
                'type' => 'heading',
                'text' => 'Secondhand više nije “ono nekad”'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Nešto se mijenja u načinu na koji gledamo odjeću. Secondhand danas nije “nemam za novo”. Ljudi kupuju vintage jer žele nešto drugačije. Prodaju odjeću jer im stoji u ormaru. I realno — zašto bi dobra jakna skupljala prašinu ako je neko drugi može nositi?'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Thrift kultura je stigla i kod nas. I iskreno, mislimo da tek počinje.'
            ],

            // Industry validation
            [
                'type' => 'heading',
                'text' => 'Nismo jedini koji to vidimo'
            ],
            [
                'type' => 'paragraph',
                'text' => '18. februara 2026. godine eBay je kupio Depop za 1.2 milijarde dolara. Kada jedna od najvećih e-commerce kompanija na svijetu ulaže toliki novac u secondhand modu, jasno je gdje se tržište kreće.'
            ],

            // Local gap
            [
                'type' => 'heading',
                'text' => 'A Bosna?'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Ako si u Bosni i Hercegovini i želiš prodati odjeću ili pronaći neki dobar komad, opcije su prilično haotične. Instagram profili, Facebook grupe, ponešto po marketplace stranicama — sve nekako razbacano, bez strukture i bez osjećaja da postoji mjesto baš za to.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Tržište postoji već dugo. Platforma nije.'
            ],

            // Name meaning
            [
                'type' => 'heading',
                'text' => 'Zašto baš Tavan?'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Tavan je mjesto gdje završavaju stvari koje volimo, ali ih više ne koristimo. Stari džemper. Vintage jakna. Patike koje su nekad bile omiljene.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Ali “Tavan” je i mala igra riječi — “ta van”. Taj komad ide van iz ormara, iako je još uvijek dobar, već je nošen i viđen.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Dva značenja. Jedna platforma.'
            ],

            // What Tavan is
            [
                'type' => 'heading',
                'text' => 'Šta je zapravo Tavan?'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Tavan je aplikacija koju otvoriš kada tražiš nešto zanimljivo za obući. Dobre komade, normalne cijene i osjećaj da možeš naletiti na nešto što nema svako drugi.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Ali dugoročna ideja ide malo dalje od same kupovine i prodaje. Želimo da Tavan postane nešto poput online ormara — pronađeš komad koji voliš, nosiš ga, i kada ti više nije zanimljiv, proslijediš ga dalje nekome kome će biti.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Istraživanja pokazuju da većina žena neke komade obuče svega nekoliko puta prije nego što završe zaboravljeni u ormaru. Tavan pokušava promijeniti baš to. Svaki komad zaslužuje više od pet izlazaka. (Kao da je muškarac pisao 😭)'
            ],

            // Timeline
            [
                'type' => 'heading',
                'text' => 'Kako je tekao naš put'
            ],

            // Nov 2025
            [
                'type' => 'subheading',
                'text' => 'Novembar 2025 — iskra'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Razgovor u kafeu koji je sve pokrenuo. Nas trojica — Minet, Safet i ja — odlučili smo da ideja ne ostane samo priča uz kafu.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Minet je smislio ime “Tavan”, kao i hrpu ideja za reklame i video promocije. Još uvijek čekamo da ih snimi.'
            ],

            // Jan 2026
            [
                'type' => 'subheading',
                'text' => 'Januar 2026 — projekt dobija oblik'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Nova godina, novi projekat. Safet preuzima dizajn i kreće graditi vizualni identitet Tavana od nule.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Prvi put radi UI/UX, pa je inspiracija dolazila sa svih strana. Malo ovo, malo ono, ali kroz taj haos aplikacija je polako počela dobijati svoj identitet.'
            ],

            // Feb 2026
            [
                'type' => 'subheading',
                'text' => '18. februar 2026 — potvrda da tržište ide u tom smjeru'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Vijest da eBay kupuje Depop došla nam je kao dodatni vjetar u leđa. Nismo jedini koji vjeruju da secondhand moda ima ogromnu budućnost.'
            ],

            // April launch socials
            [
                'type' => 'subheading',
                'text' => '1. april 2026 — da, datum zvuči kao šala. Tavan nije.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Pokrenuli smo društvene mreže i prvi put pokazali Tavan javnosti. Crno-bijeli minimalistički fazon — grafikha Safet.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Tu smo se prvi put ozbiljnije sudarili i sa marketingom. Veliko hvala prijateljima iz te oblasti koji su nam objasnili da “objavi post i čekaj” ipak nije kompletna strategija.'
            ],
            [
                'type' => 'instagram',
                'url' => 'https://www.instagram.com/p/DWl3tKNjI1I/'
            ],

            // Beta
            [
                'type' => 'subheading',
                'text' => 'April / maj 2026 — prvi beta testeri'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Bez plaćenog marketinga i bez boostanih objava, okupili smo prvih 18 beta testera koji su isprobavali aplikaciju i slali nam feedback iz dana u dan.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Slušali smo svaki komentar. Mijenjali ekrane, dodavali kategorije, popravljali stvari koje nam same nikad ne bi pale na pamet.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Čak je i vizualni identitet evoluirao usput. Tavan je krenuo kao potpuno crno-bijela aplikacija, a onda smo kroz feedback dodali roze akcentnu boju koja je danas postala dio identiteta cijelog brenda.'
            ],
            [
                'type' => 'instagram',
                'url' => 'https://www.instagram.com/p/DYPt0FlMqXi/'
            ],

            // Ending
            [
                'type' => 'heading',
                'text' => 'I šta sad?'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Nas trojica. Jedna kafa. Pun ormar odjeće. I aplikacija koja za par dana postaje dostupna svima.'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Ako želiš među prvima isprobati Tavan, zaprati nas na Instagramu @tavan.store 🪩'
            ],
            [
                'type' => 'paragraph',
                'text' => 'Počistili smo prašinu s našeg Tavana. Vrijeme je da ga otvorimo.'
            ],
        ];
    }

}

<?php

namespace Database\Seeders;

use App\Models\BlogAuthor;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Seeds the "how to" blog series that mirrors the Instagram carousel posts,
 * plus one search-intent post (gdje prodati polovnu odjeću u BiH).
 *
 * All posts are seeded UNPUBLISHED — review and publish via the admin panel
 * on your own schedule. Cover images are intentionally omitted; upload via
 * admin. Re-running the seeder overwrites text edits made in admin, so run
 * it only before editorial changes.
 */
class HowToBlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = BlogAuthor::firstOrCreate(
            ['name' => 'Edib Zahirović'],
            ['bio' => 'CEO i suosnivač Tavana']
        );

        foreach ($this->posts() as $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'title'          => $post['title'],
                    'tag'            => $post['tag'],
                    'excerpt'        => $post['excerpt'],
                    'cover_color'    => $post['cover_color'],
                    'read_time'      => $post['read_time'],
                    'blog_author_id' => $author->id,
                    'is_published'   => false,
                    'blocks'         => $post['blocks'],
                ]
            );
        }

        $this->command->info('Seeded '.count($this->posts()).' how-to blog posts (drafts).');
    }

    private function posts(): array
    {
        return [
            $this->howToBuy(),
            $this->howToSell(),
            $this->howToTrade(),
            $this->howToPhotograph(),
            $this->howToMeasure(),
            $this->firstOrder(),
            $this->whyVerification(),
            $this->whereToSellBih(),
        ];
    }

    // ─── 1. Kako kupiti ──────────────────────────────────────────────────────

    private function howToBuy(): array
    {
        return [
            'slug'        => 'kako-kupiti-na-tavanu',
            'title'       => 'Kako kupiti na Tavanu — vodič kroz prvu kupovinu',
            'tag'         => 'Vodič',
            'excerpt'     => 'Od pretrage do potvrde narudžbe — sve što trebaš znati prije prve kupovine na Tavanu, na jednom mjestu.',
            'cover_color' => '#FB5C90',
            'read_time'   => '4 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Skinuo/la si aplikaciju, skrolaš kroz artikle i vidiš komad koji ti se sviđa. Šta sad?\n\nAko si navikao/la na kupovinu preko Instagrama ili Facebook grupa, znaš onaj osjećaj: pišeš u DM, čekaš odgovor, dogovaraš uplatu i nadaš se da će paket zaista stići. Na Tavanu smo taj dio zamijenili pravom kupovinom — s narudžbom, statusima i porukama na jednom mjestu."],

                ['type' => 'heading', 'text' => 'Pronađi komad za sebe'],
                ['type' => 'paragraph', 'text' => "Kreni od pretrage ili kategorija. Možeš tražiti po nazivu, brendu ili tipu artikla — \"zara haljina\", \"levis\", \"patike 38\" — a filteri ti pomažu da suziš izbor po veličini, cijeni, stanju, boji ili gradu.\n\nAko još ne znaš šta tražiš, podesi svoje preference (veličine, brendovi, kategorije) i početna stranica će ti prikazivati komade koji ti zaista odgovaraju."],
                ['type' => 'paragraph', 'text' => 'Vidiš nešto što ti se sviđa, ali nisi spreman/na odmah kupiti? Sačuvaj artikal na listu želja — uvijek mu se možeš vratiti.'],

                ['type' => 'heading', 'text' => 'Tri načina da dođeš do artikla'],
                ['type' => 'paragraph', 'text' => "Na Tavanu imaš više opcija nego u klasičnoj trgovini:\n\n1. Kupi odmah — klasična kupovina po istaknutoj cijeni.\n2. Ponudi nižu cijenu — ako prodavač prima ponude, pošalji svoju. Prodavač je može prihvatiti, odbiti ili poslati protuponudu.\n3. Zamjena — neki prodavači prihvataju i zamjenu artikala. Više o tome u posebnom vodiču."],
                ['type' => 'paragraph', 'text' => 'A ako ti se kod istog prodavača sviđa više komada — spoji ih u paket i plati samo jednu dostavu. Ušteda je odmah vidljiva na ekranu.'],

                ['type' => 'heading', 'text' => 'Završi kupovinu'],
                ['type' => 'paragraph', 'text' => "Kada klikneš \"Kupi odmah\", biraš način dostave i plaćanja, uneseš adresu i potvrdiš narudžbu. Nakon toga sve pratiš u aplikaciji: prodavač potvrđuje narudžbu, šalje paket, a ti potvrdiš prijem kada artikal stigne.\n\nNema dopisivanja \"je l' još dostupno?\" i nadanja — sve je zabilježeno i vidljivo."],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DZ-Prh6DPF6/'],

                ['type' => 'paragraph', 'text' => 'Toliko o kupovini. Otvori aplikaciju, pogledaj šta je novo — možda te tvoj sljedeći omiljeni komad već čeka na nečijem Tavanu. 😉'],
            ],
        ];
    }

    // ─── 2. Kako prodati ─────────────────────────────────────────────────────

    private function howToSell(): array
    {
        return [
            'slug'        => 'kako-prodati-odjecu-na-tavanu',
            'title'       => 'Kako prodati odjeću na Tavanu za nekoliko minuta',
            'tag'         => 'Vodič',
            'excerpt'     => 'Ormar pun komada koje više ne nosiš? Evo kako ih pretvoriš u oglas — i u novac — za nekoliko minuta.',
            'cover_color' => '#F1F8E9',
            'read_time'   => '4 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Svako od nas ima taj dio ormara. Komadi koje smo voljeli, nosili nekoliko puta i onda... zaboravili. Dobri su, kvalitetni su, ali jednostavno više nisu \"naši\".\n\nUmjesto da skupljaju prašinu, daj im drugi život — i zaradi usput."],

                ['type' => 'heading', 'text' => 'Objavi artikal u par koraka'],
                ['type' => 'paragraph', 'text' => "Klikni na \"+\" u aplikaciji i prati korake:\n\n1. Fotografiši artikal — dobre fotografije su pola prodaje (imamo poseban vodič za to).\n2. Popuni detalje — kategorija, brend, veličina, stanje, boja i materijal. Što više informacija daš, manje pitanja dobiješ u porukama.\n3. Dodaj mjere — dužina, širina, rukavi. Kupci to zaista gledaju.\n4. Postavi cijenu — realno i pošteno. Provjeri po čemu se slični komadi prodaju.\n5. Odaberi opcije — prihvataš li ponude? Zamjene? Lično preuzimanje?"],
                ['type' => 'paragraph', 'text' => 'I to je to. Artikal je online i vidljiv svima koji traže baš takav komad.'],

                ['type' => 'heading', 'text' => 'Šta kad neko kupi?'],
                ['type' => 'paragraph', 'text' => "Dobiješ notifikaciju, potvrdiš narudžbu i spakuješ paket. Sve statuse — od potvrde do isporuke — pratiš u aplikaciji, a s kupcem se možeš dopisivati direktno u chatu ako nešto treba dogovoriti.\n\nNakon završene kupovine, kupac ti može ostaviti recenziju. Dobre recenzije = više povjerenja = brža prodaja sljedećeg komada."],

                ['type' => 'heading', 'text' => 'Mali savjeti za bržu prodaju'],
                ['type' => 'paragraph', 'text' => "— Iskrenost prodaje: ako komad ima manu, fotografiši je i napiši. Kupci cijene transparentnost.\n— Odgovaraj na poruke i ponude — protuponuda je bolja od ignorisanja.\n— Objavi više komada odjednom: kupci vole zaviriti u cijelu garderobu, a s paket kupovinom mogu uzeti više komada uz jednu dostavu."],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DZ5JnJ-jJHr/'],

                ['type' => 'paragraph', 'text' => 'Tvoj ormar je vjerovatno pun komada koje neko trenutno traži. Vrijeme je da ih pustiš van — ta van. 😉'],
            ],
        ];
    }

    // ─── 3. Zamjena ──────────────────────────────────────────────────────────

    private function howToTrade(): array
    {
        return [
            'slug'        => 'zamjena-artikala-na-tavanu',
            'title'       => 'Zamjena artikala na Tavanu — kako funkcioniše',
            'tag'         => 'Vodič',
            'excerpt'     => 'Nekad najbolja kupovina uopšte nije kupovina. Evo kako zamijeniš komad iz svog ormara za komad iz tuđeg.',
            'cover_color' => '#FB5C90',
            'read_time'   => '3 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Thrift kultura je oduvijek bila i kultura razmjene. Na buvljacima i marketima ljudi se dogovaraju, trampe i mijenjaju — pa smo taj duh prenijeli i u aplikaciju.\n\nNa Tavanu artikal ne moraš uvijek kupiti. Nekad ga možeš zamijeniti za nešto svoje."],

                ['type' => 'heading', 'text' => 'Kako predložiti zamjenu'],
                ['type' => 'paragraph', 'text' => "Ako prodavač na artiklu ima uključenu opciju zamjene, vidjet ćeš je na stranici artikla:\n\n1. Klikni na opciju zamjene.\n2. Odaberi komad (ili više njih) iz svoje garderobe koji nudiš.\n3. Pošalji prijedlog.\n\nProdavač može prihvatiti, odbiti ili poslati protuponudu — možda želi neki drugi komad iz tvoje garderobe. Zato se isplati imati više artikala objavljeno: veći izbor, veća šansa za dogovor."],

                ['type' => 'heading', 'text' => 'Zašto volimo zamjene'],
                ['type' => 'paragraph', 'text' => "Zamjena je secondhand u najčišćem obliku: dva komada dobiju nove vlasnike, niko ništa nije bacio, a obje strane su dobile \"novo\" bez trošenja.\n\nOsvježiš ormar bez da potrošiš marku. 💸"],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DZ73AtLDNlP/'],

                ['type' => 'paragraph', 'text' => 'Pogledaj svoju garderobu, pa zaviri u tuđe. Možda je tvoja sljedeća omiljena jakna samo jednu zamjenu daleko.'],
            ],
        ];
    }

    // ─── 4. Fotografisanje ───────────────────────────────────────────────────

    private function howToPhotograph(): array
    {
        return [
            'slug'        => 'kako-fotografisati-odjecu-za-prodaju',
            'title'       => 'Kako fotografisati odjeću za prodaju (i zašto je to pola prodaje)',
            'tag'         => 'Savjeti',
            'excerpt'     => 'Ista jakna, dvije fotografije — jedna se proda za dva dana, druga stoji mjesecima. Evo u čemu je razlika.',
            'cover_color' => '#F1F8E9',
            'read_time'   => '4 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Budimo iskreni: na secondhand platformama kupci prvo gledaju fotografije. Opis, mjere i cijena dolaze poslije — ako ih fotografija uopšte dovede dotle.\n\nDobra vijest? Za dobre fotografije ne treba ti skupa oprema. Treba ti telefon, prozor i pet minuta."],

                ['type' => 'heading', 'text' => 'Svjetlo je sve'],
                ['type' => 'paragraph', 'text' => "Prirodno svjetlo je tvoj najbolji prijatelj. Fotografiši danju, blizu prozora, bez blica. Vještačko svjetlo mijenja boje — a kupac koji dobije \"drugačiju\" boju od one sa slike nije sretan kupac.\n\nIzbjegavaj i direktno sunce: pravi oštre sjene. Oblačan dan je, vjerovao/la ili ne, idealan za fotografisanje odjeće."],

                ['type' => 'heading', 'text' => 'Pozadina: manje je više'],
                ['type' => 'paragraph', 'text' => 'Jednobojna pozadina — bijeli zid, vrata, čist pod — pušta komad da bude glavna zvijezda. Kreveti s dezenima, pretrpane sobe i hrpa drugih stvari u kadru odvlače pažnju i djeluju neuredno.'],

                ['type' => 'heading', 'text' => 'Pokaži komad iz svih uglova'],
                ['type' => 'paragraph', 'text' => "Minimalno: prednja strana, zadnja strana i etiketa s veličinom. Još bolje: detalji materijala, printa ili šavova.\n\nA ako komad ima manu — fleku, oštećenje, izblijedjeli dio — obavezno je fotografiši izbliza. Zvuči kontraintuitivno, ali iskrenost prodaje: kupac koji zna šta kupuje ne pravi probleme poslije."],

                ['type' => 'heading', 'text' => 'Pokaži kako komad stoji'],
                ['type' => 'paragraph', 'text' => 'Odjeća na vješalici izgleda dobro. Odjeća na osobi izgleda stvarno. Ako ti nije neugodno, uslikaj komad na sebi — kupcu je mnogo lakše zamisliti kako će mu stajati.'],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DaTF3HbDOWK/'],

                ['type' => 'paragraph', 'text' => 'Pet minuta truda oko fotografija često znači sedmice manje čekanja na prodaju. Isprobaj — i javi nam je l\' upalilo. 📸'],
            ],
        ];
    }

    // ─── 5. Mjere ────────────────────────────────────────────────────────────

    private function howToMeasure(): array
    {
        return [
            'slug'        => 'kako-izmjeriti-odjecu-za-prodaju',
            'title'       => 'Kako pravilno izmjeriti odjeću za prodaju',
            'tag'         => 'Savjeti',
            'excerpt'     => 'Veličina "M" ne znači svima isto. Mjere u centimetrima znače — i zbog njih se artikli ne vraćaju.',
            'cover_color' => '#FB5C90',
            'read_time'   => '3 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Zarina \"M\" i H&M-ova \"M\" nisu ista \"M\". A vintage \"M\" iz devedesetih? Potpuno druga priča.\n\nZato na Tavanu uz artikal možeš dodati mjere u centimetrima — i toplo preporučujemo da to uradiš. Kupci kojima je jasno hoće li im komad stajati kupuju brže i ne traže povrat."],

                ['type' => 'heading', 'text' => 'Šta ti treba'],
                ['type' => 'paragraph', 'text' => 'Kroječki metar (onaj mekani). Ako ga nemaš, posluži se i običnim metrom uz malo mašte — ili kanapom pa ga izmjeri ravnalom. Snađi se, majstore. 😅'],

                ['type' => 'heading', 'text' => 'Kako mjeriti gornje dijelove'],
                ['type' => 'paragraph', 'text' => "Komad položi ravno na pod ili sto, zakopčan:\n\n— Širina: ispod rukava, od šava do šava (pazuh do pazuha).\n— Dužina: od najviše tačke ramena do donjeg ruba.\n— Rukav: od šava na ramenu do kraja rukava."],

                ['type' => 'heading', 'text' => 'Kako mjeriti hlače i suknje'],
                ['type' => 'paragraph', 'text' => "— Struk: ravno preko pojasa, od ruba do ruba (pa pomnoži sa 2 ako kupca zanima obim).\n— Dužina: od pojasa do kraja nogavice.\n— Unutrašnja nogavica: od šava u koraku do kraja nogavice — ova mjera kupcima najviše znači."],

                ['type' => 'heading', 'text' => 'Zlatno pravilo'],
                ['type' => 'paragraph', 'text' => 'Mjeri komad, ne sebe. Kupac će svoje mjere uporediti s mjerama komada — i tačno znati hoće li mu odgovarati.'],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DaNoE4XjPaC/'],

                ['type' => 'paragraph', 'text' => 'Dvije minute mjerenja, nula nesporazuma. Dodaj mjere na svoje artikle i gledaj kako pitanja u porukama nestaju.'],
            ],
        ];
    }

    // ─── 6. Prva narudžba ────────────────────────────────────────────────────

    private function firstOrder(): array
    {
        return [
            'slug'        => 'prva-narudzba-na-tavanu',
            'title'       => 'Prva narudžba na Tavanu — šta se dešava nakon kupovine',
            'tag'         => 'Vodič',
            'excerpt'     => 'Kliknuo/la si "Potvrdi narudžbu". Šta sad? Evo kako izgleda put tvog paketa, korak po korak.',
            'cover_color' => '#F1F8E9',
            'read_time'   => '3 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Prva narudžba na novoj platformi uvijek nosi ono malo treme: \"Je l' ovo stvarno prošlo? Šta se sad dešava? Kad će paket?\"\n\nZato evo cijelog puta narudžbe, bez misterije."],

                ['type' => 'heading', 'text' => '1. Narudžba stiže prodavaču'],
                ['type' => 'paragraph', 'text' => 'Čim potvrdiš narudžbu, prodavač dobija notifikaciju i potvrđuje je. Od tog trenutka artikal je rezervisan za tebe — niko ti ga ne može preoteti.'],

                ['type' => 'heading', 'text' => '2. Paket kreće na put'],
                ['type' => 'paragraph', 'text' => "Prodavač pakuje artikal i šalje ga — a status narudžbe u aplikaciji se mijenja sa svakim korakom, pa uvijek znaš gdje je tvoj paket.\n\nAko ste se dogovorili za lično preuzimanje, jednostavno se nađete i to je to."],

                ['type' => 'heading', 'text' => '3. Paket stiže — potvrdi prijem'],
                ['type' => 'paragraph', 'text' => "Kad artikal stigne, pregledaj ga i potvrdi prijem u aplikaciji. Time se narudžba završava.\n\nAko nešto nije u redu, tu su poruke — dogovori se s prodavačem, a naša podrška je uvijek na jedan klik ako zatreba pomoć."],

                ['type' => 'heading', 'text' => '4. Ostavi recenziju'],
                ['type' => 'paragraph', 'text' => 'Recenzije su valuta povjerenja na Tavanu. Napiši kako je prošlo — pomažeš i prodavaču i svim budućim kupcima. A i prodavač ocjenjuje tebe, pa se dobra komunikacija isplati u oba smjera.'],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DaDOKoTDPwQ/'],

                ['type' => 'paragraph', 'text' => 'I to je cijela filozofija. Prva narudžba je uvijek najčudnija — druga već ide od ruke. 😉'],
            ],
        ];
    }

    // ─── 7. Sigurnost ────────────────────────────────────────────────────────

    private function whyVerification(): array
    {
        return [
            'slug'        => 'zasto-trazimo-email-i-broj-telefona',
            'title'       => 'Zašto tražimo email i broj telefona',
            'tag'         => 'Sigurnost',
            'excerpt'     => 'Iza svakog kupca i prodavača na Tavanu stoji stvarna osoba. Evo kako to osiguravamo — i šta radimo s tvojim podacima.',
            'cover_color' => '#FB5C90',
            'read_time'   => '3 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Ako si ikad kupovao/la preko oglasa ili društvenih mreža, znaš rizik: profil bez slike, bez recenzija, \"pošalji uplatu pa šaljem paket\". I onda tišina.\n\nTavan smo gradili upravo da to ne bude moguće. A prvi korak je jednostavan: iza svakog računa mora stajati stvarna osoba."],

                ['type' => 'heading', 'text' => 'Email — tvoj račun'],
                ['type' => 'paragraph', 'text' => 'Email potvrđuješ pri registraciji. On je tvoj identitet na platformi: preko njega se prijavljuješ, vraćaš pristup računu ako zaboraviš lozinku i primaš važne informacije o narudžbama.'],

                ['type' => 'heading', 'text' => 'Broj telefona — potvrda da si stvarna osoba'],
                ['type' => 'paragraph', 'text' => "Broj telefona potvrđuješ SMS kodom onda kada zaista zatreba — prije kupovine, objave artikla ili slanja poruke prodavaču. Razgledati i pretraživati možeš i bez toga.\n\nZašto ovo radimo? Jer jedan potvrđen broj = jedna stvarna osoba. Time otežavamo lažne profile, spam i prevare — i štitimo i kupce i prodavače."],

                ['type' => 'heading', 'text' => 'Šta NE radimo s tvojim brojem'],
                ['type' => 'paragraph', 'text' => "Ovo je najvažniji dio, pa ćemo ga napisati jasno:\n\n— Tvoj broj nikad nije javno vidljiv. Ni na profilu, ni na artiklu, nigdje.\n— Ne dijelimo ga s drugim korisnicima.\n— Ne šaljemo ti reklame SMS-om.\n\nBroj služi isključivo za verifikaciju. Toliko."],

                ['type' => 'heading', 'text' => 'Povjerenje se gradi u oba smjera'],
                ['type' => 'paragraph', 'text' => 'Kad kupuješ, znaš da iza prodavača stoji potvrđena osoba s recenzijama. Kad prodaješ, znaš da ti poruke ne šalju botovi. Mali korak za tebe pri registraciji — velika stvar za cijelu zajednicu.'],

                ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DZpnbpdjNkw/'],

                ['type' => 'paragraph', 'text' => 'Imaš dodatna pitanja o privatnosti? Piši nam kroz podršku u aplikaciji — odgovaramo brzo i rado.'],
            ],
        ];
    }

    // ─── 8. SEO: Gdje prodati polovnu odjeću u BiH ───────────────────────────

    private function whereToSellBih(): array
    {
        return [
            'slug'        => 'gdje-prodati-polovnu-odjecu-u-bih',
            'title'       => 'Gdje prodati polovnu odjeću u BiH — sve opcije na jednom mjestu',
            'tag'         => 'Savjeti',
            'excerpt'     => 'Instagram, Facebook grupe, thrift marketi, oglasnici ili aplikacija? Pošten pregled svih načina da prodaš odjeću u Bosni i Hercegovini.',
            'cover_color' => '#F1F8E9',
            'read_time'   => '5 min',
            'blocks'      => [
                ['type' => 'paragraph', 'text' => "Ormar ti je pun, komadi su dobri, a ti bi ih rado pretvorio/la u novac. Pitanje je samo — gdje?\n\nU BiH postoji nekoliko načina da prodaš polovnu odjeću, i svaki ima svoje prednosti i mane. Prošli smo kroz sve njih, pošteno."],

                ['type' => 'heading', 'text' => 'Instagram thrift profili'],
                ['type' => 'paragraph', 'text' => "Thrift scena u BiH godinama živi na Instagramu — i tu su nastali neki sjajni mali brendovi.\n\nPrednosti: gradiš svoj brend i zajednicu, imaš punu kontrolu nad izgledom profila.\nMane: sve je ručno. Dogovaranje u DM-ovima, vođenje evidencije ko je šta rezervisao, dokazivanje da si pouzdan/na bez recenzija... Za ozbiljan profil to je posao, ne hobi."],

                ['type' => 'heading', 'text' => 'Facebook grupe'],
                ['type' => 'paragraph', 'text' => "Grupe tipa \"kupujem/prodajem\" su ogromne i žive.\n\nPrednosti: velika publika, objava je besplatna i brza.\nMane: objava potone za par sati, kupce tražiš među stotinama komentara, a zaštite ni recenzija praktično nema. Za jedan komad — može poslužiti. Za cijeli ormar — haos."],

                ['type' => 'heading', 'text' => 'Thrift marketi'],
                ['type' => 'paragraph', 'text' => "Događaji poput thrift marketa u Sarajevu su fantastični za zajednicu — o tome smo već pisali.\n\nPrednosti: kupci uživo, odmah naplata, druženje s ljudima koji dijele tvoj stil.\nMane: dešavaju se par puta godišnje, treba platiti štand i donijeti sve na jedno mjesto. Odlična dopuna, ali ne i stalan kanal prodaje."],

                ['type' => 'heading', 'text' => 'Opšti oglasnici'],
                ['type' => 'paragraph', 'text' => "Klasični oglasnici pokrivaju sve — od automobila do frižidera. Odjeća se tu nekako izgubi.\n\nPrednosti: velika posjećenost.\nMane: nije građeno za modu. Nema veličina, mjera, stilova ni thrift zajednice — tvoja vintage jakna stoji između polovnog laptopa i garniture za sjedenje."],

                ['type' => 'heading', 'text' => 'Tavan — platforma baš za to'],
                ['type' => 'paragraph', 'text' => "Nismo objektivni, ali jesmo konkretni. Tavan je napravljen isključivo za secondhand modu u BiH:\n\n— Objava artikla traje par minuta, s kategorijama, veličinama i mjerama kakve moda traži.\n— Kupci te nalaze pretragom i filterima, ne skrolanjem kroz komentare.\n— Narudžbe, poruke i recenzije su na jednom mjestu — bez DM haosa.\n— Ponude, zamjene i paket kupovina daju kupcima razloge da uzmu više komada odjednom.\n\nA ako već imaš Instagram thrift profil — nije ili-ili. Mnogi naši prodavači koriste oboje: Instagram za brend, Tavan za prodaju bez administracije."],

                ['type' => 'heading', 'text' => 'Pa — gdje onda?'],
                ['type' => 'paragraph', 'text' => "Iskren odgovor: gdje god ti odgovara, samo nemoj da ti dobri komadi trunu u ormaru.\n\nA ako želiš da prodaja bude jednostavna, sigurna i na jednom mjestu — Tavan te čeka. Registracija traje minutu, a prvi artikal možeš objaviti već danas."],
            ],
        ];
    }
}

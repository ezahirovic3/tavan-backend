<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [

            // ── 1 ────────────────────────────────────────────────────────────────
            [
                'title'        => 'Zašto kupovati second hand odjeću? 7 razloga koji će te uvjeriti',
                'slug'         => 'zasto-kupovati-second-hand-odjecu',
                'tag'          => 'Savjeti',
                'excerpt'      => 'Second hand kupovina nije samo trend — to je pametan, ekološki i budžetski odgovoran način oblačenja. Evo 7 razloga zašto sve više Bosanaca bira half.',
                'cover_color'  => '#E8F5E9',
                'author_name'  => 'Tavan tim',
                'read_time'    => '5 min',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-04-22'),
                'blocks'       => [
                    ['type' => 'paragraph', 'text' => 'Second hand shopping — kupovina polovne odjeće — sve je popularnija u cijeloj Evropi, a polako osvaja i bosansko tržište. Razlog je jednostavan: nudi dobru robu po pristupačnim cijenama, a usput pomaže planeti. Ako si još uvijek na ogradi, ovo je 7 razloga koji će te uvjeriti da isprobas.'],
                    ['type' => 'heading', 'text' => '1. Uštedite ozbiljan novac'],
                    ['type' => 'paragraph', 'text' => 'Cijena brand nove jakne može biti 150 KM. Ista ta jakna, nošena svega par puta, na Tavanu košta 25–50 KM. Razlika je ogromna — a jakna izgleda isto. Kupovinom second hand odjeće možeš si priuštiti bolji kvalitet za manji budžet.'],
                    ['type' => 'heading', 'text' => '2. Manje otpada, zdravija planeta'],
                    ['type' => 'paragraph', 'text' => 'Tekstilna industrija je drugi najveći zagađivač na svijetu. Svake godine se baci oko 92 miliona tona odjeće globalno. Kada kupiš half komad, produljuješ mu vijek trajanja i smanjuješ potražnju za novom proizvodnjom. Jedan odjevni predmet koji traje dulje = manje CO₂ u atmosferi.'],
                    ['type' => 'heading', 'text' => '3. Pronađi unikatne komade'],
                    ['type' => 'paragraph', 'text' => 'Na Tavanu nećeš naći 500 identičnih majica. Svaki komad je jedinstven — vintage denim jakna iz 90-ih, retro sport trenerka, dizajnerska haljina od nekoga ko ju je jednom obukao. Second hand je raj za one koji žele da se oblače drugačije od mase.'],
                    ['type' => 'heading', 'text' => '4. Kvalitet koji traje'],
                    ['type' => 'paragraph', 'text' => 'Starija odjeća je često pravljena od boljih materijala nego današnja fast fashion. Pamuk koji je gušći, šavovi koji ne pucaju — komadi koji su preživjeli deceniju već su dokazali da su kvalitetni.'],
                    ['type' => 'heading', 'text' => '5. Podržavaš lokalnu zajednicu'],
                    ['type' => 'paragraph', 'text' => 'Kupovinom na Tavanu novac ostaje u Bosni. Pomažeš osobi do tebe, ne stranom veleprodavcu. Zajednica je ta koja profitira — i to je nešto vrijedno.'],
                    ['type' => 'heading', 'text' => '6. Eksperimentišeš bez rizika'],
                    ['type' => 'paragraph', 'text' => 'Nisi siguran/na da li ti odgovara određeni stil? Kupi second hand komad za 10 KM i provjeri. Ako se ne uklopi, ponovo ga prodaj. Bez gubitka, bez žaljenja.'],
                    ['type' => 'heading', 'text' => '7. Kupovina postaje zabava'],
                    ['type' => 'paragraph', 'text' => 'Listanje Tavana i traženje savršenog komada ima u sebi nešto kao "lov na blago". Nikad ne znaš što ćeš pronaći — i to je čini zabavnijim od kupovine u klasičnoj prodavnici.'],
                    ['type' => 'quote', 'text' => 'Najbolja odjeća je ona koju neko drugi više ne nosi, a tebi savršeno pristaje.', 'author' => 'Tavan tim'],
                    ['type' => 'paragraph', 'text' => 'Preuzmi Tavan app, pretraži hiljade komada od bosanskih prodavača i pronađi svoju sljedeću omiljenu odjeću — za manje novca i s dobrim osjećajem.'],
                ],
            ],

            // ── 2 ────────────────────────────────────────────────────────────────
            [
                'title'        => 'Kako prodati odjeću online u Bosni: korak po korak vodič',
                'slug'         => 'kako-prodati-odjecu-online-bosna',
                'tag'          => 'Savjeti',
                'excerpt'      => 'Imaš hrpu odjeće koja skuplja prašinu? Pretvori je u gotovinu. Ovaj vodič ti pokazuje kako brzo i sigurno prodati odjeću online putem Tavan aplikacije.',
                'cover_color'  => '#FFF3E0',
                'author_name'  => 'Tavan tim',
                'read_time'    => '6 min',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-04-28'),
                'blocks'       => [
                    ['type' => 'paragraph', 'text' => 'Ormar pun odjeće koju ne nosiš? Nisi sam/a. Prosječna osoba nosi samo 20% svoje garderobe — ostalo visi i čeka. Umjesto da doniraš ili bacaš, možeš zaraditi. Platforma Tavan ti omogućuje da prodaš odjeću direktno kupcima u Bosni, jednostavno i sigurno.'],
                    ['type' => 'heading', 'text' => 'Korak 1: Izaberi šta prodaješ'],
                    ['type' => 'paragraph', 'text' => 'Prođi kroz ormar i izdvoji sve što nisi obukao/la u posljednjih godinu dana. Posebno traži: brendiranu odjeću, sportsku opremu, zimske jakne, haljine za posebne prilike i dječiju odjeću — ovo je najtraženije na Tavanu.'],
                    ['type' => 'subheading', 'text' => 'Šta se dobro prodaje?'],
                    ['type' => 'paragraph', 'text' => 'Nike, Adidas, Zara, H&M, Reserved, Pull&Bear, Tommy Hilfiger — brendovi koji se brzo prodaju. Zimske jakne i kaputima posebno dobro idu u sezoni. Dječija odjeća se prodaje skoro uvijek jer djeca brzo rastu.'],
                    ['type' => 'heading', 'text' => 'Korak 2: Uslikaj kao profesionalac'],
                    ['type' => 'paragraph', 'text' => 'Fotografija je razlika između komada koji se proda za dan i onog koji stoji sedmicama. Evo trika: pazi na osvjetljenje — prirodno svjetlo uz prozor je idealno. Fotografiraj na čistoj, neutralnoj podlozi — bijeli pod, sivi zid. Snimi odjeću razvučenu ravno i na sebi (selfie u ogledalu radi odlično). Uključi detalje — etiketu s veličinom, jedinstven detalj, boju u detaljima.'],
                    ['type' => 'heading', 'text' => 'Korak 3: Napiši opis koji prodaje'],
                    ['type' => 'paragraph', 'text' => 'Kupac ne može opipati odjeću, pa mu treba sve informacije. Uvijek navedi: brend i model (ako znaš), veličinu, materijal ako je poznat, stanje (novo s etiketom, korišten 2x, redovito korišten), razlog prodaje — kupci vole znati.'],
                    ['type' => 'subheading', 'text' => 'Primjer dobrog opisa'],
                    ['type' => 'paragraph', 'text' => '"Zara zimska jakna, veličina M, crna. Korišćena jednu sezonu, bez oštećenja. Prodajem jer sam promijenila stil. Materijal: 80% polyester, topla i lagana." — ovakav opis odgovara na sva pitanja kupca unaprijed.'],
                    ['type' => 'heading', 'text' => 'Korak 4: Postavi pravu cijenu'],
                    ['type' => 'paragraph', 'text' => 'Previsoka cijena = nema prodaje. Preniska = gubiš novac. Opće pravilo: postavi 30–50% od originalne cijene za redovno korištenu odjeću. Za skoro novu odjeću (nošenu 1–2x) možeš ići i do 60–70%. Pogledaj slične oglase na Tavanu i prilagodi se tržištu.'],
                    ['type' => 'heading', 'text' => 'Korak 5: Brzo reaguj na upite'],
                    ['type' => 'paragraph', 'text' => 'Kupci koji pošalju poruku žele brz odgovor. Što brže odgovoriš, veće su šanse za prodaju. Kroz Tavan aplikaciju možeš chatovati direktno s kupcem, dogovoriti pickup ili dostavu i primiti uplatu sigurno unutar platforme.'],
                    ['type' => 'quote', 'text' => 'Jedna dobra fotografija vrijedi više od deset opisa.', 'author' => 'Tavan prodavači'],
                    ['type' => 'paragraph', 'text' => 'Preuzmi Tavan, dodaj prve oglase i za nekoliko minuta si aktivni/a prodavač/ica. Ormar čišći, džep deblji.'],
                ],
            ],

            // ── 3 ────────────────────────────────────────────────────────────────
            [
                'title'        => 'Second hand moda u Bosni: zašto je ovo trenutak koji smo čekali',
                'slug'         => 'second-hand-moda-bosna',
                'tag'          => 'Moda',
                'excerpt'      => 'Evropa je davno prihvatila second hand kao legitiman modni izbor. Bosna je na redu. Gledamo kako se mijenja odnos prema polovnoj odjeći i zašto je to dobra vijest za sve.',
                'cover_color'  => '#F3E5F5',
                'author_name'  => 'Tavan tim',
                'read_time'    => '4 min',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-05-02'),
                'blocks'       => [
                    ['type' => 'paragraph', 'text' => 'Prije deset godina u Bosni je kupovina polovne odjeće imala stigmu. Bila je to opcija samo kad nema druge. Danas, 2026. godine, sve se promijenilo — i to brže nego što smo mislili.'],
                    ['type' => 'heading', 'text' => 'Generacija Z predvodi promjenu'],
                    ['type' => 'paragraph', 'text' => 'Mladi između 18 i 30 godina su ti koji guraju second hand u mainstream. Thrift shopping (kupovina u vintage i second hand shopovima) je postao cool — nije više sramota, nego ponos. Nositi vintage Levi\'s traperice ili pronađenu jaknu iz 90-ih je danas statement, ne kompromis.'],
                    ['type' => 'paragraph', 'text' => 'Bosanski mladi prate te trendove. TikTok i Instagram su puni Bosanaca koji pokazuju svoja "thrift haul" nalaženja. Zajednica raste.'],
                    ['type' => 'heading', 'text' => 'Ekonomska realnost koja tjera pametan izbor'],
                    ['type' => 'paragraph', 'text' => 'Inflacija je pogodila modnu industriju — cijene nove odjeće porasle su za 20–30% u posljednje dvije godine. U isto vrijeme, plaće u Bosni nisu pratile taj rast. Rezultat: više i više ljudi traži alternativu. Second hand nije kompromis — to je pametan finansijski izbor.'],
                    ['type' => 'heading', 'text' => 'Digitalne platforme kao okidač'],
                    ['type' => 'paragraph', 'text' => 'Vinted je u Evropi stvorio pravu revoluciju. Milioni transakcija dnevno, zajednica koja samo raste. Bosna do sada nije imala lokaliziranu alternativu — niti u jeziku, niti u načinu plaćanja, niti u razumijevanju lokalnog tržišta.'],
                    ['type' => 'paragraph', 'text' => 'Tavan je tu da popuni tu prazninu. Platforma koja je dizajnirana za Bosnu — na bosanskom jeziku, prilagođena lokalnim navikama kupovine, s cijenama u KM.'],
                    ['type' => 'heading', 'text' => 'Kako izgleda bosanski second hand kupac?'],
                    ['type' => 'paragraph', 'text' => 'Na osnovu razgovora s prvim korisnicima Tavana, portret tipičnog kupca je: žena ili muškarac između 20 i 40 godina, živi u gradu, pazi na budžet ali ne želi kompromitovati stil. Traži brendiranu odjeću po razumnoj cijeni. Cijeni kvalitet iznad kvantiteta.'],
                    ['type' => 'quote', 'text' => 'Second hand nije plan B. To je plan koji ima smisla.', 'author' => 'Tavan tim'],
                    ['type' => 'paragraph', 'text' => 'Bosna je spremna za ovu promjenu. Tavan je tu da je olakša — jednom transakcijom odjednom.'],
                ],
            ],

            // ── 4 ────────────────────────────────────────────────────────────────
            [
                'title'        => 'Upoznaj Tavan: bosanska aplikacija za kupovinu i prodaju odjeće',
                'slug'         => 'upoznaj-tavan-aplikacija',
                'tag'          => 'Tavan',
                'excerpt'      => 'Tavan je prva bosanska second hand fashion platforma. Kupuj i prodaj odjeću, obuću i modne dodatke direktno od osoba u tvojoj blizini — jednostavno, sigurno i u KM.',
                'cover_color'  => '#E3F2FD',
                'author_name'  => 'Tavan tim',
                'read_time'    => '3 min',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-05-05'),
                'blocks'       => [
                    ['type' => 'paragraph', 'text' => 'Tavan nije samo još jedna aplikacija. To je odgovor na pitanje koje su mnogi postavljali: zašto Bosna nema svoju Vinted platformu? Sada ima.'],
                    ['type' => 'heading', 'text' => 'Šta je Tavan?'],
                    ['type' => 'paragraph', 'text' => 'Tavan je mobilna aplikacija za kupovinu i prodaju half (polovne) odjeće, obuće i modnih dodataka. Dizajnirana je isključivo za bosansko tržište — na bosanskom jeziku, s cijenama u KM i s razumijevanjem načina na koji mi ovdje kupujemo i prodajemo.'],
                    ['type' => 'heading', 'text' => 'Kako funkcioniše?'],
                    ['type' => 'paragraph', 'text' => 'Registracija traje manje od minute. Fotografišeš odjeću, dodaš opis i cijenu, i tvoj oglas je vidljiv kupcima širom Bosne. Kupci browse-aju, lajkaju, šalju ponude i dogovaraju se direktno s tobom kroz aplikaciju. Nema posrednika, nema skrivenih naknada.'],
                    ['type' => 'subheading', 'text' => 'Za prodavače'],
                    ['type' => 'paragraph', 'text' => 'Dodaj oglas za manje od 2 minute. Postavi svoju cijenu ili prihvati ponude. Chat direktno s kupcem. Završi transakciju sigurno unutar aplikacije.'],
                    ['type' => 'subheading', 'text' => 'Za kupce'],
                    ['type' => 'paragraph', 'text' => 'Pretraži po brendu, veličini, kategoriji i lokaciji. Sačuvaj favourite. Pošalji ponudu ako smatraš da je cijena visoka. Kupi s povjerenjem — Tavan štiti transakcije.'],
                    ['type' => 'heading', 'text' => 'Zašto "Tavan"?'],
                    ['type' => 'paragraph', 'text' => 'Tavan je metafora za ono što svako od nas ima doma — kutiju, vreću ili cijelu sobu odjeće koju više ne nosimo. Dragocjenosti koje skupljaju prašinu. Tavan aplikacija je mjesto gdje ta odjeća dobija novu šansu — i gdje ti dobijaš malo više mjesta u ormaru.'],
                    ['type' => 'quote', 'text' => 'Svaki tavan krije nečije blago. Nađi ga na Tavanu.', 'author' => 'Tavan tim'],
                    ['type' => 'paragraph', 'text' => 'Preuzmi Tavan app — besplatno, bez obaveza. Pridruži se zajednici koja mijenja način na koji se Bosna oblači.'],
                ],
            ],

            // ── 5 ────────────────────────────────────────────────────────────────
            [
                'title'        => '5 načina kako stilizovati second hand nalaze kao pravi stilist',
                'slug'         => 'kako-stilizovati-second-hand-odjecu',
                'tag'          => 'Moda',
                'excerpt'      => 'Kupiš second hand komad ali ne znaš kako ga uklopiti? Ovi savjeti od stilista ti pomažu da od half nalaza napraviš outfite koji izgledaju skupo i promišljeno.',
                'cover_color'  => '#FCE4EC',
                'author_name'  => 'Tavan tim',
                'read_time'    => '5 min',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-05-08'),
                'blocks'       => [
                    ['type' => 'paragraph', 'text' => 'Jedna od najvećih prednosti second hand shoppinga je sloboda — nema kolekcija, nema "ovosezonskih" pravila, nema pritiska da pratiš trendove. Ali upravo ta sloboda može biti zbunjujuća. Kako složiti outfit od komada koji nisu zamišljeni da idu zajedno? Evo pet principa koje stilisti zapravo koriste.'],
                    ['type' => 'heading', 'text' => '1. Izgraditi kapsulu garderobu od osnova'],
                    ['type' => 'paragraph', 'text' => 'Prije nego tražiš zanimljive komade, osiguraj osnove: bijela majica, crne hlače ili traperice, neutralni džemper, klasična jakna. Ovi komadi idu uz sve. Kad imaš solidnu bazu, svaki interesantan second hand nalaz postaje lako uklopiv — čak i ona neobična štampana bluza ili oversized blazer.'],
                    ['type' => 'heading', 'text' => '2. Igraj se proporcijama'],
                    ['type' => 'paragraph', 'text' => 'Oversized gornji dio + slim donji dio = balansiran outfit. Isto vrijedi i obrnuto. Second hand odjeća često dolazi u veličinama koje nisu savršene — i to je ok. Prevelika košulja tucked in u high-waist hlače izgleda namjerno i stilski. Prevelika trenerka s uskim jeggings-ima je klasičan streetwear look.'],
                    ['type' => 'subheading', 'text' => 'Trik s tucking-om'],
                    ['type' => 'paragraph', 'text' => '"Half tuck" — ubaciti samo prednji dio majice u hlače — je jedan od najlakših trikova za odmah polisiran izgled. Radi s gotovo svakom kombinacijom.'],
                    ['type' => 'heading', 'text' => '3. Miješaj stilove bez straha'],
                    ['type' => 'paragraph', 'text' => 'Vintage jean jakna + elegantna midi haljina = savršena napetost između casual i fancy. Sportska trenerka + loafers = cool mix high-low. Second hand je savršeno tlo za mixing stilova jer imaš pristup komadima iz različitih decenija i stilskih era.'],
                    ['type' => 'heading', 'text' => '4. Obrati pažnju na materijale i teksture'],
                    ['type' => 'paragraph', 'text' => 'Outfit napravljen od jedne teksture može biti dosadan. Pomiješaj — svileni komad s denim-om, kožnu jaknu s mekanim pletivom, strukturirani blazer s relaxed pamukom. Ova igra teksturama čini outfit zanimljivim čak i kad su boje sasvim neutralne.'],
                    ['type' => 'heading', 'text' => '5. Neka jedan komad bude zvijezda'],
                    ['type' => 'paragraph', 'text' => 'Pronašao/la si nevjerovatnu vintage jaknu? Neka ona govori. Ostatak outfita drži neutralnim. Pronašla si unikatan print? Sve ostalo u solidnim, komplementarnim bojama. Stilisti zovu ovo "one hero piece" princip — i radi svaki put. Ne moraš da se takmičiš sa svakim komadom koji nosiš.'],
                    ['type' => 'quote', 'text' => 'Stil nije o tome koliko trošiš. Radi se o tome šta vidiš i kako kombinuješ.', 'author' => 'Tavan tim'],
                    ['type' => 'paragraph', 'text' => 'Sljedeći put kad otvoriš Tavan i pronađeš neki zanimljiv komad — ne pitaj se "kuda ću ovo nositi?" nego "koji od ovih principa mi pomaže da ga uklopim?" Odgovor gotovo uvijek postoji.'],
                ],
            ],

            // ── 6 ────────────────────────────────────────────────────────────────
            [
                'title'        => 'Cirkularna moda: kako tvoja stara odjeća može spasiti okoliš',
                'slug'         => 'cirkularna-moda-okolish-bosna',
                'tag'          => 'Zajednica',
                'excerpt'      => 'Fast fashion je jedan od najvećih ekoloških problema modernog doba. Saznaj kako cirkularna ekonomija u modi funkcioniše i šta možeš učiniti već danas u Bosni.',
                'cover_color'  => '#E8F5E9',
                'author_name'  => 'Tavan tim',
                'read_time'    => '4 min',
                'is_published' => true,
                'published_at' => Carbon::parse('2026-05-10'),
                'blocks'       => [
                    ['type' => 'paragraph', 'text' => 'Zamislite rijeku. U nju svake sekunde ulazi nova odjeća — majice, jakne, džins, haljine — a s druge strane teku otpad i hemikalije. To je gruba slika onoga šta fast fashion radi okolišu. Svake godine globalna industrija mode proizvede 100 milijardi komada odjeće. Od toga, procjenjuje se da se 92 miliona tona završi na deponijama.'],
                    ['type' => 'heading', 'text' => 'Šta je cirkularna moda?'],
                    ['type' => 'paragraph', 'text' => 'Cirkularna ekonomija u modi znači zatvoriti krug — umjesto linearnog modela (proizvedi → kupi → baci), odjeća se ponovo koristi, preprodaje, popravlja ili reciklira. Cilj je što duže zadržati odjevne predmete u upotrebi i što manje ih baciti.'],
                    ['type' => 'paragraph', 'text' => 'Kupovina second hand odjeće je jedan od najdirektnih načina da učestvuješ u cirkularnoj ekonomiji. Ne treba ti posebno znanje ni investicija — samo aplikacija i volja da potražiš komad od nekoga drugog umjesto u novoj prodavnici.'],
                    ['type' => 'heading', 'text' => 'Brojke koje govore'],
                    ['type' => 'paragraph', 'text' => 'Produljenje vijeka korišćenja jednog odjevnog predmeta za samo 9 mjeseci smanjuje ugljični, vodeni i otpadni otisak tog komada za 20–30%. Jedna jeans hlače treba oko 7.500 litara vode za proizvodnju — dovoljno za jednu osobu da pije 10 godina. Kada kupiš polovne traperice, spašavaš tu vodu.'],
                    ['type' => 'heading', 'text' => 'Šta možeš učiniti odmah?'],
                    ['type' => 'subheading', 'text' => 'Prodaj umjesto da baciš'],
                    ['type' => 'paragraph', 'text' => 'Odjeća koja ti ne treba nije otpad — to je nečiji novi omiljeni komad. Dodaj oglas na Tavanu i pusti je da nastavi živjeti u tuđem ormaru.'],
                    ['type' => 'subheading', 'text' => 'Kupi second hand kao prvi izbor'],
                    ['type' => 'paragraph', 'text' => 'Sljedeći put kad ti treba nešto novo — jakna, haljina, trenerka — prvo provjeri Tavan. Šanse su dobre da ćeš naći upravo to, u dobrom stanju, za pola cijene.'],
                    ['type' => 'subheading', 'text' => 'Popravi umjesto da odmah menjaš'],
                    ['type' => 'paragraph', 'text' => 'Pukao šav? Nedostaje dugme? Obućar i krojačica mogu produžiti život komada za godine. U Bosni imamo sjajne zanatlije — iskoristi ih.'],
                    ['type' => 'quote', 'text' => 'Svaki put kad kupiš second hand, jedan komad odjeće ne završi na smetljištu. To je mali, ali realan doprinos.', 'author' => 'Tavan tim'],
                    ['type' => 'paragraph', 'text' => 'Tavan nije samo aplikacija za prodaju — to je zajednica koja vjeruje da se može lijepo oblačiti i čuvati okoliš istovremeno. Pridruži nam se.'],
                ],
            ],

        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                $post
            );
        }

        $this->command->info('Seeded ' . count($posts) . ' blog posts.');
    }
}

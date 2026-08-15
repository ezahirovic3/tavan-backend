<?php

namespace Database\Seeders;

use App\Models\BlogAuthor;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Seeds the post-festival occasion-wear resale post.
 *
 * Search-intent post ("gdje prodati svečanu haljinu") with a Sarajevo Film
 * Festival hook in the intro — timely for late August, evergreen the rest of
 * the year. Same framework works for mature, vjenčanja and Nova godina.
 *
 * Seeded UNPUBLISHED — review, add cover image and publish via the admin panel.
 * Re-running the seeder overwrites text edits made in admin.
 */
class PostFestivalBlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = BlogAuthor::firstOrCreate(
            ['name' => 'Edib Zahirović'],
            ['bio' => 'CEO i suosnivač Tavana']
        );

        BlogPost::updateOrCreate(
            ['slug' => 'gdje-prodati-svecanu-haljinu-u-bih'],
            [
                'title'          => 'Šta poslije festivala — gdje prodati svečanu haljinu u BiH',
                'tag'            => 'Savjeti',
                'excerpt'        => 'Sarajevo Film Festival je završio, a haljina se vraća u ormar — da čeka “neku sljedeću priliku”. Evo šta možeš uraditi umjesto toga.',
                'cover_color'    => '#FB5C90',
                'read_time'      => '5 min',
                'blog_author_id' => $author->id,
                'is_published'   => false,
                'blocks'         => $this->blocks(),
            ]
        );

        $this->command->info('Seeded "šta poslije festivala" blog post (draft).');
    }

    private function blocks(): array
    {
        return [
            ['type' => 'paragraph', 'text' => "Osam dana projekcija, crvenog tepiha i večeri koje su se otegle duže nego što je iko planirao. Sarajevo Film Festival je završio, ekipa se razišla, a fotografije su odavno na Instagramu.\n\nA haljina? Haljina se vratila u ormar. U onu navlaku, na kraj šipke, gdje će čekati “neku sljedeću priliku”."],
            ['type' => 'paragraph', 'text' => 'Poznato? Kod nas skoro svako ima barem jedan takav komad. Svečanu haljinu, odijelo, sako, cipele obuvene jednom. To je istovremeno najskuplji i najmanje nošen dio ormara.'],

            ['type' => 'heading', 'text' => 'Sljedeća prilika obično ne dođe'],
            ['type' => 'paragraph', 'text' => "Svečana odjeća ima čudan status. Kupimo je za jednu večer, a čuvamo je godinama — jer je bila skupa, jer nam je lijepa i jer nam se čini da bi bilo šteta pustiti je.\n\nAli budimo iskreni: kad ta sljedeća prilika konačno dođe, rijetko kad želimo obući istu haljinu. Pogotovo ne pred istim ljudima."],
            ['type' => 'paragraph', 'text' => 'U međuvremenu komad samo stoji. Ne troši se, ne kvari se — samo stoji.'],

            ['type' => 'heading', 'text' => 'Zašto se svečana odjeća zapravo odlično prodaje'],
            ['type' => 'paragraph', 'text' => "Upravo zato što se malo nosi. Svečani komadi su gotovo uvijek u odličnom stanju — nema izlizanih laktova ni ispranih boja, obično su nošeni jednom ili dvaput.\n\nA potražnja postoji cijele godine: mature, promocije, vjenčanja, svadbe, Nova godina. Uvijek negdje neko traži haljinu za jednu večer i ne želi za nju izdvojiti pola plate."],
            ['type' => 'paragraph', 'text' => 'To je usput i najbolji argument za kupca. Ako je ionako obučeš jednom, zašto bi platila punu cijenu?'],

            ['type' => 'heading', 'text' => 'Koliko tražiti'],
            ['type' => 'paragraph', 'text' => "Ovdje se najviše griješi, i to u oba smjera.\n\nKao gruba orijentacija: za komad u odličnom stanju, nošen jednom ili dvaput, negdje između 40 i 60 posto originalne cijene je realno. Prepoznatljivi brendovi i kvalitetni materijali drže vrijednost bolje, komad iz brze mode pada brže."],
            ['type' => 'paragraph', 'text' => 'Ali najbolji pokazatelj nije etiketa nego tržište. Pretraži slične komade na Tavanu i gledaj po kojim se cijenama zaista prodaju, ne po kojim se postavljaju.'],
            ['type' => 'paragraph', 'text' => 'I ostavi sebi malo prostora. Ako na oglasu dozvoliš ponude, kupac ti može poslati svoju cijenu, a ti je možeš prihvatiti, odbiti ili poslati protuponudu. Tako se dogovor često završi brže nego čekanjem na punu cijenu.'],

            ['type' => 'heading', 'text' => 'Tri stvari prije nego postaviš oglas'],
            ['type' => 'paragraph', 'text' => "1. Očisti i ispeglaj. Kod svečanih materijala se svaka bora vidi na fotografiji. Ako je haljina bila na hemijskom čišćenju, još bolje — napiši to u opisu.\n\n2. Fotografiši po dnevnom svjetlu. Saten, čipka i šljokice najgore izgledaju pod žutim sijalicama. Na vješalici pored prozora, pa još nekoliko detalja izbliza. Detaljnije u [vodiču o fotografisanju odjeće za prodaju](/blog/kako-fotografisati-odjecu-za-prodaju).\n\n3. Napiši mjere. Kod svečanih haljina veličina “M” ne znači ništa. Kroj je uži, materijal često ne popušta. Obim grudi, struka i ukupna dužina uštede vrijeme i tebi i kupcu — a kako se tačno mjeri, pokazali smo u [vodiču o mjerenju](/blog/kako-izmjeriti-odjecu-za-prodaju)."],
            ['type' => 'paragraph', 'text' => 'I još jedna sitnica koja ne zvuči važno, a jeste: napiši gdje si je nosila. “Nošena jednom, na projekciji na SFF-u” prodaje bolje od “kao nova”. Priča daje komadu kontekst, a kupcu sigurnost.'],

            ['type' => 'heading', 'text' => 'Ako ti je ipak žao da je pustiš'],
            ['type' => 'paragraph', 'text' => "Onda je nemoj prodati — zamijeni je.\n\nNa Tavanu možeš ponuditi [zamjenu za drugi komad](/blog/zamjena-artikala-na-tavanu). Tvoja haljina ode nekome ko je nikad nije vidio, a ti dobiješ nešto novo za sljedeću priliku. Bez trošenja i bez praznog mjesta u ormaru."],

            ['type' => 'heading', 'text' => 'Nije samo haljina'],
            ['type' => 'paragraph', 'text' => "Isto vrijedi za odijelo obučeno na vjenčanje prije dvije godine. Za sako koji si obukao jednom. Za svečane cipele koje te žuljaju otkad si ih kupio. Za malu torbicu koja ide samo uz tu haljinu.\n\nProšeći kroz taj dio ormara i budi iskren/a sa sobom: koliko od toga ćeš zaista još obući?"],

            // Zamijeni URL-om odgovarajuće objave (npr. SFF/svečani outfit karusel).
            ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/DYPt0FlMqXi/'],

            ['type' => 'paragraph', 'text' => "Festival traje osam dana. Haljina ne mora čekati do sljedećeg augusta.\n\nSlikaj je dok je još ispeglana i postavi oglas — nekome je već sada potrebna za neku svoju večer. 😉"],

            ['type' => 'link', 'text' => 'Kako postaviti oglas', 'url' => '/blog/kako-prodati-odjecu-na-tavanu', 'style' => 'primary'],
        ];
    }
}

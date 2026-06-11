<?php

namespace App\Filament\Resources\BrandSuggestions;

use App\Filament\Resources\BrandSuggestions\Pages\ListBrandSuggestions;
use App\Filament\Resources\BrandSuggestions\Pages\ViewBrandSuggestion;
use App\Models\Brand;
use App\Models\BrandSuggestion;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BrandSuggestionResource extends Resource
{
    protected static ?string $model = BrandSuggestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Prijedlozi brendova';

    protected static ?string $modelLabel = 'prijedlog';

    protected static ?string $pluralModelLabel = 'prijedlozi brendova';

    protected static ?int $navigationSort = 21;

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('status', 'pending')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary'; // pink — pulls attention
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prijedlog')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Predloženi naziv')->weight('bold'),
                        TextEntry::make('user.username')->label('Korisnik')->prefix('@'),
                        TextEntry::make('created_at')->label('Datum')->dateTime('d.m.Y. H:i'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'gray',
                            }),
                        TextEntry::make('note')->label('Napomena')->columnSpanFull()->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Predloženi naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('user.username')
                    ->label('Korisnik')
                    ->prefix('@')
                    ->searchable()
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending'  => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])->default('pending'),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Odobri')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->schema([
                        TextInput::make('name')
                            ->label('Naziv brenda')
                            ->required()
                            ->default(fn ($record) => $record->name),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->extraInputAttributes(['class' => 'font-mono'])
                            ->default(fn ($record) => Str::slug($record->name)),

                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->disk('r2')
                            ->directory('brands/logos'),
                    ])
                    ->modalHeading('Odobri prijedlog → kreiraj brend')
                    ->modalSubmitActionLabel('Kreiraj brend')
                    ->action(function (array $data, $record) {
                        if (Brand::where('slug', $data['slug'])->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Brend već postoji')
                                ->body('Brend sa slug-om "' . $data['slug'] . '" već postoji u katalogu.')
                                ->send();

                            return;
                        }

                        Brand::create([
                            'name' => $data['name'],
                            'slug' => $data['slug'],
                            'logo' => $data['logo'] ?? null,
                            'active' => true,
                            'sort_order' => Brand::max('sort_order') + 1,
                        ]);

                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Brend kreiran')
                            ->body('Brend "' . $data['name'] . '" je dodan u katalog.')
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Odbaci')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Odbaci prijedlog')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->success()->title('Prijedlog odbačen')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isSuperAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrandSuggestions::route('/'),
            'view'  => ViewBrandSuggestion::route('/{record}'),
        ];
    }
}

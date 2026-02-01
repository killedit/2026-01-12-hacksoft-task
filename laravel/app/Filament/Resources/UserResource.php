<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),

            Placeholder::make('password_hash')
                ->label('Current password hash')
                ->content(fn ($record) => $record?->password ?? '—'),

            TextInput::make('password')
                ->label('New password (optional)')
                ->password()
                ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            TextInput::make('description')->nullable(),

            FileUpload::make('profile_picture')
                ->label('Profile Picture')
                ->image()
                ->avatar()
                // ->imageEditor()
                // ->circular()
                ->disk('public')
                ->directory('profile-pictures')
                ->visibility('public')
                ->deletable()
                ->downloadable()
                ->previewable(true)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                // ->maxSize(5120)
                ->nullable(),

            Toggle::make('is_approved')->label('Approved'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                ImageColumn::make('profile_picture')
                    ->disk('public')
                    ->visibility('public')
                    ->circular()
                    ->label('Avatar'),
                IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                TextColumn::make('description')->limit(50),
            ])
            ->filters([
                Filter::make('unapproved')
                    ->label('Unapproved Users')
                    ->query(fn ($query) => $query->where('is_approved', false)),
                TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),
            ])
            ->recordUrl(
                fn (User $record): string => route('filament.admin.resources.users.edit', $record)
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_approved', false)->count();
    }
}

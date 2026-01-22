<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // public static function form(Schema $schema): Schema
    // {
    //     return PostForm::configure($schema);
    // }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Post Details')
                ->schema([
                    // \Filament\Forms\Components\Select::make('user_id')
                    //     ->relationship('author', 'name')
                    //     ->required()
                    //     ->label('Author'),
                    \Filament\Forms\Components\Placeholder::make('author_name')
                    ->label('Author')
                    ->content(fn ($record) => $record?->author?->name ?? auth()->user()->name),

                    \Filament\Forms\Components\Textarea::make('content')
                        ->required()
                        ->maxLength(1000)
                        ->rows(5),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        // return PostsTable::configure($table);
        return $table
        ->columns([
            TextColumn::make('author.name')->label('Author'),
            TextColumn::make('content')->limit(50),
            TextColumn::make('deleted_at')->dateTime()->label('Deleted At')->placeholder('Not Deleted'),
        ])
        ->filters([
            TrashedFilter::make(),
        ])
        ->actions([
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
            \Filament\Actions\RestoreAction::make(),
        ]);
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
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Limit resource to posts of the authenticated user only.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

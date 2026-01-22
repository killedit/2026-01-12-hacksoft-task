<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            // ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        if ($this->getRecord()->user_id !== auth()->id()) {
            abort(403);
        }
    }
}

<?php

return [
    'path' => 'admin',
    'widgets' => [
        App\Filament\Widgets\AccountWidget::class,
        App\Filament\Widgets\FilamentInfoWidget::class,
    ],
    'resources' => [
        App\Filament\Resources\Users\UserResource::class,
        // other resources
    ],
];

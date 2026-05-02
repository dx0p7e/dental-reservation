<?php

namespace App\Filament\Resources\Doctors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DoctorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('specialization')
                    ->required()
                    ->maxLength(255),
                Textarea::make('bio')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                FileUpload::make('photo_path')
                    ->label(__('filament.fields.profile_photo'))
                    ->disk('public')
                    ->directory('doctors')
                    ->image()
                    ->nullable()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
                Select::make('services')
                    ->multiple()
                    ->relationship('services', 'name'),
            ]);
    }
}

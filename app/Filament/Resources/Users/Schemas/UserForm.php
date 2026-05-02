<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rule(fn (?Model $record) => Rule::unique('users', 'email')->ignore($record?->id)),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->hiddenOn('edit'),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password')
                    ->saved(false)
                    ->hiddenOn('edit'),
                Select::make('role')
                    ->options([
                        'patient' => __('filament.options.patient'),
                        'doctor' => __('filament.options.doctor'),
                        'admin' => __('filament.options.admin'),
                    ])
                    ->default('patient')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->nullable(),
                Toggle::make('email_verified_at')
                    ->label(__('filament.fields.mark_email_verified'))
                    ->formatStateUsing(fn ($state): bool => $state !== null),
                Toggle::make('phone_verified_at')
                    ->label(__('filament.fields.mark_phone_verified'))
                    ->formatStateUsing(fn ($state): bool => $state !== null),
                Select::make('notification_channel')
                    ->options([
                        'email' => __('filament.options.email_channel'),
                        'sms' => __('filament.options.sms'),
                        'both' => __('filament.options.both'),
                    ])
                    ->default('email')
                    ->required(),
                Section::make(__('filament.sections.loyalty_account'))
                    ->hiddenOn('create')
                    ->schema([
                        Placeholder::make('loyalty_tier')
                            ->label(__('filament.fields.tier'))
                            ->content(fn (?User $record): string => $record?->loyaltyAccount?->tier ?? '—'),
                        Placeholder::make('loyalty_points')
                            ->label(__('filament.fields.points_balance'))
                            ->content(fn (?User $record): string => $record?->loyaltyAccount
                                ? (string) $record->loyaltyAccount->points_balance
                                : '—'),
                    ])
                    ->columns(2),
            ]);
    }
}

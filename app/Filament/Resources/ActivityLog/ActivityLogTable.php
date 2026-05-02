<?php

namespace App\Filament\Resources\ActivityLog;

use App\Models\Appointment;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('filament.fields.date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('event')
                    ->label(__('filament.fields.event'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label(__('filament.fields.subject'))
                    ->formatStateUsing(fn (?string $state, mixed $record): string => $state !== null
                        ? class_basename($state).' #'.$record->subject_id
                        : '—')
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label(__('filament.fields.causer'))
                    ->default(__('filament.options.system'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('filament.fields.description'))
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => __('filament.options.created'),
                        'updated' => __('filament.options.updated'),
                        'deleted' => __('filament.options.deleted'),
                    ]),
                SelectFilter::make('subject_type')
                    ->label(__('filament.filters.subject_type'))
                    ->options([
                        Appointment::class => __('filament.options.appointment_type'),
                        LoyaltyTransaction::class => __('filament.options.loyalty_transaction'),
                        LoyaltyAccount::class => __('filament.options.loyalty_account_type'),
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

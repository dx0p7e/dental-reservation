<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->description(fn (User $record): string => $record->email_verified_at
                        ? '✓ Patvirtintas'
                        : '✗ Nepatvirtintas'),
                TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—')
                    ->description(fn (User $record): ?string => $record->phone
                        ? ($record->phone_verified_at ? '✓ Patvirtintas' : '✗ Nepatvirtintas')
                        : null),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'doctor' => 'warning',
                        default => 'primary',
                    }),
                TextColumn::make('loyaltyAccount.tier')
                    ->label(__('filament.fields.loyalty_tier'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('loyaltyAccount.points_balance')
                    ->label('Taškai')
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('smart_id_verified_at')
                    ->label('Smart-ID')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Patvirtinta' : 'Nepatvirtinta')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'patient' => __('filament.options.patient'),
                        'doctor' => __('filament.options.doctor'),
                        'admin' => __('filament.options.admin'),
                    ]),
                TernaryFilter::make('email_verified_at')
                    ->label(__('filament.filters.email_verified'))
                    ->nullable()
                    ->trueLabel(__('filament.options.verified'))
                    ->falseLabel(__('filament.options.not_verified')),
                TernaryFilter::make('phone_verified_at')
                    ->label(__('filament.filters.phone_verified'))
                    ->nullable()
                    ->trueLabel(__('filament.options.verified'))
                    ->falseLabel(__('filament.options.not_verified')),
                SelectFilter::make('loyalty_tier')
                    ->label(__('filament.filters.loyalty_tier'))
                    ->options([
                        'standard' => __('filament.options.standard'),
                        'silver' => __('filament.options.silver'),
                        'gold' => __('filament.options.gold'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereHas('loyaltyAccount', fn (Builder $q) => $q->where('tier', $data['value']))
                        : $query),
            ])
            ->recordActions([
                Action::make('verifyEmail')
                    ->label(__('filament.actions.verify_email'))
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->hidden(fn (User $record): bool => $record->email_verified_at !== null)
                    ->action(function (User $record): void {
                        $record->update(['email_verified_at' => now()]);
                        Notification::make()->title(__('filament.notifications.email_verified'))->success()->send();
                    }),
                Action::make('verifyPhone')
                    ->label(__('filament.actions.verify_phone'))
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->hidden(fn (User $record): bool => $record->phone_verified_at !== null)
                    ->action(function (User $record): void {
                        $record->update(['phone_verified_at' => now()]);
                        Notification::make()->title(__('filament.notifications.phone_verified'))->success()->send();
                    }),
                Action::make('revokeVerifications')
                    ->label(__('filament.actions.revoke_verifications'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update(['email_verified_at' => null, 'phone_verified_at' => null]);
                        Notification::make()->title(__('filament.notifications.verifications_revoked'))->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('verifyEmails')
                        ->label(__('filament.actions.verify_emails'))
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            User::whereIn('id', $records->pluck('id'))->update(['email_verified_at' => now()]);
                            Notification::make()->title(__('filament.notifications.emails_verified'))->success()->send();
                        }),
                    DeleteBulkAction::make()
                        ->using(function (Collection $records): void {
                            $records->filter(fn (User $record): bool => $record->id !== auth()->id())
                                ->each->delete();
                        }),
                ]),
            ]);
    }
}

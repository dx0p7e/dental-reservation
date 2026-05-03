<?php

namespace App\Filament\Resources\DoctorSchedules\Tables;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\SlotGenerationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DoctorSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('doctor.user.name')
                    ->label(__('filament.fields.doctor'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('day_of_week')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => __('filament.days.mon'),
                        2 => __('filament.days.tue'),
                        3 => __('filament.days.wed'),
                        4 => __('filament.days.thu'),
                        5 => __('filament.days.fri'),
                        6 => __('filament.days.sat'),
                        7 => __('filament.days.sun'),
                        default => (string) $state,
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1, 2, 3, 4, 5 => 'primary',
                        6 => 'warning',
                        7 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label(__('filament.fields.start_time'))
                    ->time()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label(__('filament.fields.end_time'))
                    ->time()
                    ->sortable(),
                TextColumn::make('slot_duration_minutes')
                    ->suffix(' min')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('filament.fields.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('doctor_id')
                    ->label(__('filament.fields.doctor'))
                    ->options(Doctor::with('user')->get()->pluck('user.name', 'id')),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => __('filament.options.active'),
                        '0' => __('filament.options.inactive'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('generateSlots')
                    ->label(__('filament.actions.generate_slots'))
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextInput::make('days')
                            ->label(__('filament.fields.days_ahead'))
                            ->numeric()
                            ->default(14)
                            ->minValue(1)
                            ->maxValue(365)
                            ->required(),
                    ])
                    ->action(function (array $data, DoctorSchedule $record, SlotGenerationService $service): void {
                        $result = $service->generateForSchedule($record, now(), (int) $data['days']);

                        if ($result['created'] === 0) {
                            Notification::make()
                                ->warning()
                                ->title(__('filament.notifications.no_slots.title'))
                                ->body(__('filament.notifications.no_slots.body'))
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title(__('filament.notifications.slots_generated.title'))
                                ->body(__('filament.notifications.slots_generated.body', ['created' => $result['created'], 'skipped' => $result['skipped']]))
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

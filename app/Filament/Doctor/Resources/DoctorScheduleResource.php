<?php

namespace App\Filament\Doctor\Resources;

use App\Filament\Doctor\Resources\DoctorScheduleResource\Pages\CreateDoctorSchedule;
use App\Filament\Doctor\Resources\DoctorScheduleResource\Pages\EditDoctorSchedule;
use App\Filament\Doctor\Resources\DoctorScheduleResource\Pages\ListDoctorSchedules;
use App\Models\DoctorSchedule;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DoctorScheduleResource extends Resource
{
    protected static ?string $model = DoctorSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.my_schedule');
    }

    public static function getModelLabel(): string
    {
        return __('filament.model.doctor_schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.model.doctor_schedules');
    }

    public static function getEloquentQuery(): Builder
    {
        $doctor = auth()->user()?->doctor;

        if (! $doctor) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('doctor_id', $doctor->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('day_of_week')
                    ->label(__('filament.fields.day_of_week'))
                    ->options([
                        1 => __('filament.days.monday'),
                        2 => __('filament.days.tuesday'),
                        3 => __('filament.days.wednesday'),
                        4 => __('filament.days.thursday'),
                        5 => __('filament.days.friday'),
                        6 => __('filament.days.saturday'),
                        7 => __('filament.days.sunday'),
                    ])
                    ->required(),
                TimePicker::make('start_time')
                    ->label(__('filament.fields.start_time'))
                    ->required(),
                TimePicker::make('end_time')
                    ->label(__('filament.fields.end_time'))
                    ->required(),
                TextInput::make('slot_duration_minutes')
                    ->label(__('filament.fields.slot_duration'))
                    ->numeric()
                    ->minValue(1)
                    ->suffix('min')
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('filament.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_of_week')
                    ->label(__('filament.fields.day_of_week'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => __('filament.days.monday'),
                        2 => __('filament.days.tuesday'),
                        3 => __('filament.days.wednesday'),
                        4 => __('filament.days.thursday'),
                        5 => __('filament.days.friday'),
                        6 => __('filament.days.saturday'),
                        7 => __('filament.days.sunday'),
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
                    ->label(__('filament.fields.slot_duration'))
                    ->suffix(' min')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('filament.fields.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDoctorSchedules::route('/'),
            'create' => CreateDoctorSchedule::route('/create'),
            'edit'   => EditDoctorSchedule::route('/{record}/edit'),
        ];
    }
}

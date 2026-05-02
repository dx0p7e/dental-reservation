<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Doctor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password'),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['password' => Hash::make($data['password'])]);
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['email_verified_at'] = isset($data['email_verified_at']) && $data['email_verified_at'] !== null;
        $data['phone_verified_at'] = isset($data['phone_verified_at']) && $data['phone_verified_at'] !== null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['email_verified_at'] = $data['email_verified_at']
            ? ($this->record->email_verified_at ?? now())
            : null;

        $data['phone_verified_at'] = $data['phone_verified_at']
            ? ($this->record->phone_verified_at ?? now())
            : null;

        return $data;
    }

    protected function afterSave(): void
    {
        DB::transaction(function (): void {
            $this->record->syncRoles([$this->record->role]);

            if ($this->record->role === 'doctor') {
                Doctor::firstOrCreate(
                    ['user_id' => $this->record->id],
                    ['is_active' => true, 'specialization' => '', 'bio' => '']
                );
            }
        });
    }
}

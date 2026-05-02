<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Doctor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = $data['email_verified_at'] ? now() : null;
        $data['phone_verified_at'] = $data['phone_verified_at'] ? now() : null;

        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
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

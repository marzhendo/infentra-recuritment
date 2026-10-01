<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    
    protected static ?string $navigationLabel = 'Akun POH';
    protected static ?string $pluralModelLabel = 'Akun POH';
    protected static ?string $modelLabel = 'Akun';

    public static function canAccess(): bool
    {
        return auth()->user()?->is_head_interviewer ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->default('-'),
                IconColumn::make('has_email')
                    ->label('Email Diatur')
                    ->boolean()
                    ->state(fn (User $record): bool => !empty($record->email)),
                IconColumn::make('has_password')
                    ->label('Sandi Diatur')
                    ->boolean()
                    ->state(fn (User $record): bool => !empty($record->password)),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('atur_akun')
                    ->label('Atur akun')
                    ->icon(Heroicon::OutlinedKey)
                    ->fillForm(fn (User $record): array => [
                        'email' => $record->email,
                    ])
                    ->schema([
                        Placeholder::make('warning')
                            ->label('')
                            ->content(new HtmlString('<div class="text-danger-600 dark:text-danger-400">Akun yang hanya menggunakan NIM (tanpa sandi) memiliki tingkat keamanan yang rendah.</div>')),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(\App\Models\User::class, 'email', ignoreRecord: true)
                            ->required(),
                        TextInput::make('password')
                            ->label('Kata Sandi Baru')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn ($record) => empty($record->password)), // Required if they don't have one
                    ])
                    ->action(function (User $record, array $data): void {
                        $updateData = ['email' => $data['email']];
                        if (filled($data['password'])) {
                            $updateData['password'] = Hash::make($data['password']);
                        }
                        $record->update($updateData);
                    }),
                \Filament\Actions\Action::make('hapus_sandi')
                    ->label('Hapus kata sandi')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedTrash)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'password' => null,
                        ]);
                    })
                    ->visible(fn (User $record) => !empty($record->password)),
            ])
            ->toolbarActions([
                // No bulk actions needed
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}

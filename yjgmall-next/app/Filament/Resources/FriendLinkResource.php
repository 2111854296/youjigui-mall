<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FriendLinkResource\Pages;
use App\Models\FriendLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FriendLinkResource extends Resource
{
    protected static ?string $model = FriendLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = '友情链接';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '友情链接';

    protected static ?string $pluralModelLabel = '友情链接';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->label('链接')
                    ->url()
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('friend-links'),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('启用')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('名称')->searchable(),
                Tables\Columns\TextColumn::make('url')->label('链接')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('sort')->label('排序')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('启用')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('启用'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFriendLinks::route('/'),
            'create' => Pages\CreateFriendLink::route('/create'),
            'edit' => Pages\EditFriendLink::route('/{record}/edit'),
        ];
    }
}

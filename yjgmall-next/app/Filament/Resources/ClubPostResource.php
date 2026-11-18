<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubPostResource\Pages;
use App\Models\ClubPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClubPostResource extends Resource
{
    protected static ?string $model = ClubPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = '商圈';

    protected static ?string $navigationGroup = '扩展模块';

    protected static ?string $modelLabel = '商圈';

    protected static ?string $pluralModelLabel = '商圈';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('circle')
                    ->label('圈子')
                    ->maxLength(255),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\TextInput::make('views')
                    ->label('浏览')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('circle')->label('圈子')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('views')->label('浏览')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'offline' => '已下架',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubPosts::route('/'),
            'create' => Pages\CreateClubPost::route('/create'),
            'edit' => Pages\EditClubPost::route('/{record}/edit'),
        ];
    }
}

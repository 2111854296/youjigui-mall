<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftResource\Pages;
use App\Models\Gift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GiftResource extends Resource
{
    protected static ?string $model = Gift::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = '积分礼品';

    protected static ?string $navigationGroup = '扩展模块';

    protected static ?string $modelLabel = '积分礼品';

    protected static ?string $pluralModelLabel = '积分礼品';

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
                Forms\Components\FileUpload::make('thumb')
                    ->label('缩略图')
                    ->image()
                    ->directory('gifts'),
                Forms\Components\TextInput::make('points')
                    ->label('积分')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('stock')
                    ->label('库存')
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('published'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\ImageColumn::make('thumb')->label('缩略图'),
                Tables\Columns\TextColumn::make('points')->label('积分')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('库存')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
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
            'index' => Pages\ListGifts::route('/'),
            'create' => Pages\CreateGift::route('/create'),
            'edit' => Pages\EditGift::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = '商品评价';

    protected static ?string $navigationGroup = '商城';

    protected static ?string $modelLabel = '商品评价';

    protected static ?string $pluralModelLabel = '商品评价';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('商品')
                    ->relationship('product', 'title')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('rating')
                    ->label('评分')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('content')
                    ->label('评价内容')
                    ->rows(4)
                    ->columnSpanFull()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'hidden' => '已隐藏',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('product.title')->label('商品')->limit(30)->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('rating')->label('评分')->sortable(),
                Tables\Columns\TextColumn::make('content')->label('内容')->limit(40),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'hidden' => '已隐藏',
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
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReviews::route('/'),
            'edit' => Pages\EditProductReview::route('/{record}/edit'),
        ];
    }
}

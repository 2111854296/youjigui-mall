<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = '商品';

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->label('分类')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('thumb')
                    ->label('商品图片')
                    ->image()
                    ->disk('public')
                    ->directory('uploads/products')
                    ->maxSize(5120),
                Forms\Components\TextInput::make('price')
                    ->label('价格')
                    ->numeric()
                    ->prefix('¥')
                    ->required(),
                Forms\Components\TextInput::make('stock')
                    ->label('库存')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('unit')
                    ->label('单位')
                    ->maxLength(50),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('详情')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('uploads/editor')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\ImageColumn::make('thumb')->label('图')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('分类'),
                Tables\Columns\TextColumn::make('price')->label('价格')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('库存')->sortable(),
                Tables\Columns\TextColumn::make('unit')->label('单位'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

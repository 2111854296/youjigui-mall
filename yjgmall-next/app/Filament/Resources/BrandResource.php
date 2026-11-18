<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = '品牌';

    protected static ?string $navigationGroup = '内容';

    protected static ?string $modelLabel = '品牌';

    protected static ?string $pluralModelLabel = '品牌';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('brands'),
                Forms\Components\TextInput::make('website')
                    ->label('官网')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('country')
                    ->label('国家')
                    ->maxLength(255),
                Forms\Components\Textarea::make('intro')
                    ->label('简介')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_featured')
                    ->label('推荐')
                    ->default(false),
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
                Tables\Columns\TextColumn::make('name')->label('名称')->searchable(),
                Tables\Columns\ImageColumn::make('logo')->label('Logo'),
                Tables\Columns\TextColumn::make('country')->label('国家')->toggleable(),
                Tables\Columns\TextColumn::make('sort')->label('排序')->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->label('推荐')->boolean(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('views')->label('浏览')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured')->label('推荐'),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteProductResource\Pages;
use App\Models\QuoteProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuoteProductResource extends Resource
{
    protected static ?string $model = QuoteProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = '行情产品';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '行情产品';

    protected static ?string $pluralModelLabel = '行情产品';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('产品名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit')
                    ->label('单位')
                    ->maxLength(50),
                Forms\Components\TextInput::make('price_min')
                    ->label('最低价')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('price_max')
                    ->label('最高价')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('price_avg')
                    ->label('均价')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('market')
                    ->label('市场')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('quoted_on')
                    ->label('报价日期'),
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
                Tables\Columns\TextColumn::make('name')->label('产品名称')->searchable(),
                Tables\Columns\TextColumn::make('unit')->label('单位'),
                Tables\Columns\TextColumn::make('price_min')->label('最低价')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('price_max')->label('最高价')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('price_avg')->label('均价')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('market')->label('市场')->toggleable(),
                Tables\Columns\TextColumn::make('quoted_on')->label('报价日期')->date()->sortable(),
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
            'index' => Pages\ListQuoteProducts::route('/'),
            'create' => Pages\CreateQuoteProduct::route('/create'),
            'edit' => Pages\EditQuoteProduct::route('/{record}/edit'),
        ];
    }
}

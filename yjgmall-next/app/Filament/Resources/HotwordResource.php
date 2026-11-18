<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotwordResource\Pages;
use App\Models\Hotword;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HotwordResource extends Resource
{
    protected static ?string $model = Hotword::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = '热词';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '热词';

    protected static ?string $pluralModelLabel = '热词';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('word')
                    ->label('热词')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('module')
                    ->label('模块')
                    ->options([
                        'sell' => '供应',
                        'buy' => '求购',
                        'company' => '企业',
                        'news' => '资讯',
                        'mall' => '商城',
                    ])
                    ->required()
                    ->default('sell'),
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
                Tables\Columns\TextColumn::make('word')->label('热词')->searchable(),
                Tables\Columns\TextColumn::make('module')->label('模块')->badge(),
                Tables\Columns\TextColumn::make('sort')->label('排序')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('启用')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('模块')
                    ->options([
                        'sell' => '供应',
                        'buy' => '求购',
                        'company' => '企业',
                        'news' => '资讯',
                        'mall' => '商城',
                    ]),
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
            'index' => Pages\ListHotwords::route('/'),
            'create' => Pages\CreateHotword::route('/create'),
            'edit' => Pages\EditHotword::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpreadResource\Pages;
use App\Models\Spread;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SpreadResource extends Resource
{
    protected static ?string $model = Spread::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = '排名推广';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '排名推广';

    protected static ?string $pluralModelLabel = '排名推广';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('module')
                    ->label('模块')
                    ->options([
                        'sell' => '供应',
                        'buy' => '求购',
                        'company' => '企业',
                        'mall' => '商城',
                    ])
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('keyword')
                    ->label('关键词')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('bid')
                    ->label('出价')
                    ->numeric()
                    ->prefix('¥')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('days')
                    ->label('天数')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'active' => '已生效',
                        'rejected' => '已拒绝',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('开始时间'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('结束时间'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('module')->label('模块')->badge(),
                Tables\Columns\TextColumn::make('keyword')->label('关键词')->searchable(),
                Tables\Columns\TextColumn::make('bid')->label('出价')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('days')->label('天数'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('starts_at')->label('开始时间')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('结束时间')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'active' => '已生效',
                        'rejected' => '已拒绝',
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
            'index' => Pages\ListSpreads::route('/'),
            'edit' => Pages\EditSpread::route('/{record}/edit'),
        ];
    }
}

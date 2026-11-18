<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = '订单';

    protected static ?string $modelLabel = '订单';

    protected static ?string $pluralModelLabel = '订单';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_no')
                    ->label('订单号')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('amount')
                    ->label('金额')
                    ->numeric()
                    ->prefix('¥')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'shipped' => '已发货',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                        'refunded' => '已退款',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('payment_method')
                    ->label('支付方式')
                    ->maxLength(50)
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('receiver_name')
                    ->label('收货人')
                    ->maxLength(255),
                Forms\Components\TextInput::make('receiver_phone')
                    ->label('收货电话')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\Textarea::make('receiver_address')
                    ->label('收货地址')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('order_no')->label('订单号')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('金额')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('payment_method')->label('支付方式'),
                Tables\Columns\TextColumn::make('receiver_name')->label('收货人')->toggleable(),
                Tables\Columns\TextColumn::make('receiver_phone')->label('电话')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('下单时间')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'shipped' => '已发货',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                        'refunded' => '已退款',
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

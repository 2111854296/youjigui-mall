<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawRequestResource\Pages;
use App\Models\WithdrawRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawRequestResource extends Resource
{
    protected static ?string $model = WithdrawRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = '提现申请';

    protected static ?string $navigationGroup = '财务';

    protected static ?string $modelLabel = '提现申请';

    protected static ?string $pluralModelLabel = '提现申请';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->label('会员')->relationship('user', 'username')->searchable()->required(),
            Forms\Components\TextInput::make('amount')->label('金额')->numeric()->required(),
            Forms\Components\TextInput::make('bank_name')->label('开户行'),
            Forms\Components\TextInput::make('bank_account')->label('账号'),
            Forms\Components\TextInput::make('account_name')->label('户名'),
            Forms\Components\Select::make('status')->label('状态')->options([
                'pending' => '待审核',
                'approved' => '已通过',
                'rejected' => '已拒绝',
            ])->required()->default('pending'),
            Forms\Components\Textarea::make('remark')->label('备注')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.username')->label('会员')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('金额')->sortable(),
                Tables\Columns\TextColumn::make('bank_name')->label('开户行'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('申请时间')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawRequests::route('/'),
            'edit' => Pages\EditWithdrawRequest::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

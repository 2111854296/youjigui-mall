<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AskTicketResource\Pages;
use App\Models\AskTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AskTicketResource extends Resource
{
    protected static ?string $model = AskTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = '问客服';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '问客服';

    protected static ?string $pluralModelLabel = '问客服';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->label('会员')->relationship('user', 'username')->searchable(),
            Forms\Components\TextInput::make('title')->label('标题')->required(),
            Forms\Components\Textarea::make('content')->label('内容')->rows(4)->columnSpanFull(),
            Forms\Components\TextInput::make('contact_name')->label('联系人'),
            Forms\Components\TextInput::make('contact_phone')->label('电话'),
            Forms\Components\Select::make('status')->label('状态')->options([
                'new' => '新工单',
                'replied' => '已回复',
                'closed' => '已关闭',
            ])->default('new')->required(),
            Forms\Components\Textarea::make('reply')->label('回复')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('user.username')->label('会员'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('时间')->dateTime()->sortable(),
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
            'index' => Pages\ListAskTickets::route('/'),
            'edit' => Pages\EditAskTicket::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = '询盘';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '询盘';

    protected static ?string $pluralModelLabel = '询盘';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('contact_name')
                    ->label('联系人')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('contact_email')
                    ->label('联系邮箱')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->label('内容')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'new' => '新询盘',
                        'processing' => '处理中',
                        'done' => '已完成',
                    ])
                    ->required()
                    ->default('new'),
                Forms\Components\Select::make('to_user_id')
                    ->label('接收用户')
                    ->relationship('toUser', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('contact_name')->label('联系人')->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')->label('联系电话')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'new' => '新询盘',
                        'processing' => '处理中',
                        'done' => '已完成',
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
            'index' => Pages\ListInquiries::route('/'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}

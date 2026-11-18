<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = '求购';

    protected static ?string $modelLabel = '求购';

    protected static ?string $pluralModelLabel = '求购';

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
                    ->label('标题图片')
                    ->image()
                    ->disk('public')
                    ->directory('uploads/purchases')
                    ->maxSize(5120),
                Forms\Components\TextInput::make('budget')
                    ->label('预算')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('quantity')
                    ->label('数量')
                    ->maxLength(100),
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
                Forms\Components\DateTimePicker::make('refreshed_at')
                    ->label('刷新时间'),
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
                Tables\Columns\TextColumn::make('budget')->label('预算')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('数量'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('refreshed_at')->label('刷新时间')->dateTime()->sortable(),
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
                    Tables\Actions\BulkAction::make('approve')
                        ->label('批量通过')
                        ->action(fn ($records) => $records->each->update(['status' => 'approved'])),
                    Tables\Actions\BulkAction::make('reject')
                        ->label('批量拒绝')
                        ->action(fn ($records) => $records->each->update(['status' => 'rejected'])),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupDealResource\Pages;
use App\Models\GroupDeal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GroupDealResource extends Resource
{
    protected static ?string $model = GroupDeal::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = '团购';

    protected static ?string $navigationGroup = '扩展模块';

    protected static ?string $modelLabel = '团购';

    protected static ?string $pluralModelLabel = '团购';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('thumb')
                    ->label('缩略图')
                    ->image()
                    ->directory('group-deals'),
                Forms\Components\TextInput::make('price')
                    ->label('团购价')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('market_price')
                    ->label('市场价')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('stock')
                    ->label('库存')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('sold')
                    ->label('已售')
                    ->numeric()
                    ->default(0),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('结束时间'),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('published'),
                Forms\Components\TextInput::make('views')
                    ->label('浏览')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\ImageColumn::make('thumb')->label('缩略图'),
                Tables\Columns\TextColumn::make('price')->label('团购价')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('库存')->sortable(),
                Tables\Columns\TextColumn::make('sold')->label('已售')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('结束时间')->dateTime()->sortable()->toggleable(),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroupDeals::route('/'),
            'create' => Pages\CreateGroupDeal::route('/create'),
            'edit' => Pages\EditGroupDeal::route('/{record}/edit'),
        ];
    }
}

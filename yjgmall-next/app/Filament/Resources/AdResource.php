<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdResource\Pages;
use App\Models\Ad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = '广告';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '广告';

    protected static ?string $pluralModelLabel = '广告';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('position')
                    ->label('位置')
                    ->options([
                        'home_banner' => '首页横幅',
                        'home_right' => '首页右侧',
                        'sell_right' => '供应右侧',
                        'buy_right' => '求购右侧',
                        'mall_top' => '商城顶部',
                        'news_top' => '资讯顶部',
                        'footer' => '页脚',
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'image' => '图片',
                        'text' => '文字',
                        'code' => '代码',
                    ])
                    ->required()
                    ->default('image'),
                Forms\Components\FileUpload::make('image')
                    ->label('图片')
                    ->image()
                    ->directory('ads'),
                Forms\Components\TextInput::make('link')
                    ->label('链接')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->label('内容')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('启用')
                    ->default(true),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('开始时间'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('结束时间'),
                Forms\Components\TextInput::make('price')
                    ->label('价格')
                    ->numeric()
                    ->prefix('¥'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('position')->label('位置')->badge(),
                Tables\Columns\TextColumn::make('type')->label('类型')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('启用')->boolean(),
                Tables\Columns\TextColumn::make('sort')->label('排序')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('结束时间')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label('位置')
                    ->options([
                        'home_banner' => '首页横幅',
                        'home_right' => '首页右侧',
                        'sell_right' => '供应右侧',
                        'buy_right' => '求购右侧',
                        'mall_top' => '商城顶部',
                        'news_top' => '资讯顶部',
                        'footer' => '页脚',
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
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
        ];
    }
}

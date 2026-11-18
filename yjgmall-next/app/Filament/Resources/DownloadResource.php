<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownloadResource\Pages;
use App\Models\Download;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = '下载';

    protected static ?string $navigationGroup = '内容';

    protected static ?string $modelLabel = '下载';

    protected static ?string $pluralModelLabel = '下载';

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
                Forms\Components\Select::make('category_id')
                    ->label('分类')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('file_url')
                    ->label('文件地址')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('file_type')
                    ->label('文件类型')
                    ->maxLength(30),
                Forms\Components\TextInput::make('file_size')
                    ->label('文件大小')
                    ->maxLength(30),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('published'),
                Forms\Components\TextInput::make('downloads')
                    ->label('下载次数')
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
                Tables\Columns\TextColumn::make('category.name')->label('分类')->sortable(),
                Tables\Columns\TextColumn::make('file_type')->label('类型')->toggleable(),
                Tables\Columns\TextColumn::make('file_size')->label('大小')->toggleable(),
                Tables\Columns\TextColumn::make('downloads')->label('下载次数')->sortable(),
                Tables\Columns\TextColumn::make('sort')->label('排序')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
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
            ->defaultSort('sort');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDownloads::route('/'),
            'create' => Pages\CreateDownload::route('/create'),
            'edit' => Pages\EditDownload::route('/{record}/edit'),
        ];
    }
}

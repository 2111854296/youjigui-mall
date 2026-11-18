<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobPostResource\Pages;
use App\Models\JobPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobPostResource extends Resource
{
    protected static ?string $model = JobPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = '招聘';

    protected static ?string $navigationGroup = '扩展模块';

    protected static ?string $modelLabel = '招聘';

    protected static ?string $pluralModelLabel = '招聘';

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
                Forms\Components\TextInput::make('company_name')
                    ->label('公司名称')
                    ->maxLength(255),
                Forms\Components\TextInput::make('area')
                    ->label('地区')
                    ->maxLength(255),
                Forms\Components\TextInput::make('salary')
                    ->label('薪资')
                    ->maxLength(255),
                Forms\Components\TextInput::make('education')
                    ->label('学历')
                    ->maxLength(255),
                Forms\Components\TextInput::make('experience')
                    ->label('经验')
                    ->maxLength(255),
                Forms\Components\TextInput::make('headcount')
                    ->label('人数')
                    ->numeric()
                    ->default(1),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('contact_name')
                    ->label('联系人')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'approved' => '已通过',
                        'pending' => '待审核',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('pending'),
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
                Tables\Columns\TextColumn::make('company_name')->label('公司')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('area')->label('地区')->toggleable(),
                Tables\Columns\TextColumn::make('salary')->label('薪资')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('views')->label('浏览')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'approved' => '已通过',
                        'pending' => '待审核',
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
            'index' => Pages\ListJobPosts::route('/'),
            'create' => Pages\CreateJobPost::route('/create'),
            'edit' => Pages\EditJobPost::route('/{record}/edit'),
        ];
    }
}

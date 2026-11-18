<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestLeadResource\Pages;
use App\Models\InvestLead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestLeadResource extends Resource
{
    protected static ?string $model = InvestLead::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = '招商线索';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '招商线索';

    protected static ?string $pluralModelLabel = '招商线索';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invest_project_id')
                    ->label('招商项目')
                    ->relationship('project', 'title')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('company_name')
                    ->label('公司名称')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('contact_name')
                    ->label('联系人')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('content')
                    ->label('留言')
                    ->rows(3)
                    ->columnSpanFull()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'new' => '新线索',
                        'done' => '已处理',
                        'rejected' => '已拒绝',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('project.title')->label('招商项目')->limit(30)->sortable(),
                Tables\Columns\TextColumn::make('company_name')->label('公司')->searchable(),
                Tables\Columns\TextColumn::make('contact_name')->label('联系人')->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')->label('联系电话')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'new' => '新线索',
                        'done' => '已处理',
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
            'index' => Pages\ListInvestLeads::route('/'),
            'edit' => Pages\EditInvestLead::route('/{record}/edit'),
        ];
    }
}

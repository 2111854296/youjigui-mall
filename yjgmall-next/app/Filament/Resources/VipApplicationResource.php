<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VipApplicationResource\Pages;
use App\Models\VipApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VipApplicationResource extends Resource
{
    protected static ?string $model = VipApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'VIP申请';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = 'VIP申请';

    protected static ?string $pluralModelLabel = 'VIP申请';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                    ])
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('vip_grade_id')
                    ->label('VIP等级')
                    ->relationship('grade', 'name')
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
                Forms\Components\TextInput::make('phone')
                    ->label('电话')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('mobile')
                    ->label('手机')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('email')
                    ->label('邮箱')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('qq')
                    ->label('QQ')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(3)
                    ->columnSpanFull()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('company_name')->label('公司名称')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('contact_name')->label('联系人')->searchable(),
                Tables\Columns\TextColumn::make('mobile')->label('手机')->searchable(),
                Tables\Columns\TextColumn::make('grade.name')->label('VIP等级'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('通过')
                    ->color('success')
                    ->visible(fn (VipApplication $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (VipApplication $record) {
                        $record->update(['status' => 'approved']);
                        $record->loadMissing('grade', 'user');
                        if ($record->grade && $record->user) {
                            $record->user->update([
                                'level' => $record->grade->level,
                                'vip_expire_at' => now()->addYear(),
                                'is_certified' => true,
                            ]);
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('拒绝')
                    ->color('danger')
                    ->visible(fn (VipApplication $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (VipApplication $record) => $record->update(['status' => 'rejected'])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approveSelected')
                        ->label('批量通过')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'approved']);
                                $record->loadMissing('grade', 'user');
                                if ($record->grade && $record->user) {
                                    $record->user->update([
                                        'level' => $record->grade->level,
                                        'vip_expire_at' => now()->addYear(),
                                        'is_certified' => true,
                                    ]);
                                }
                            }
                        }),
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
            'index' => Pages\ListVipApplications::route('/'),
            'edit' => Pages\EditVipApplication::route('/{record}/edit'),
        ];
    }
}

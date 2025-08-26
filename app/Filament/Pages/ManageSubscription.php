<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ManageSubscription extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.manage-subscription';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 50;
    
    protected static ?string $title = 'Manage Subscription';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pending Subscription')
                    ->schema([
                        Forms\Components\Placeholder::make('pending_plan')
                            ->label('Requested Plan')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'No shop associated with your account.';
                                }
                                
                                $pendingSubscription = $shop->pendingSubscription();
                                if (!$pendingSubscription) {
                                    return 'No pending subscription requests.';
                                }
                                
                                return $pendingSubscription->plan->name;
                            }),
                        
                        Forms\Components\Placeholder::make('requested_date')
                            ->label('Requested On')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'N/A';
                                }
                                
                                $pendingSubscription = $shop->pendingSubscription();
                                if (!$pendingSubscription) {
                                    return 'N/A';
                                }
                                
                                return $pendingSubscription->created_at->format('M d, Y');
                            }),
                    ])
                    ->columns(2)
                    ->visible(function () {
                        $shop = Auth::user()->shop;
                        return $shop && $shop->pendingSubscription();
                    }),
                
                Forms\Components\Section::make('Current Subscription')
                    ->schema([
                        Forms\Components\Placeholder::make('current_plan')
                            ->label('Current Plan')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'No shop associated with your account.';
                                }
                                
                                $subscription = $shop->activeSubscription();
                                if (!$subscription) {
                                    return 'No active subscription.';
                                }
                                
                                return $subscription->plan->name;
                            }),
                        
                        Forms\Components\Placeholder::make('status')
                            ->label('Status')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'N/A';
                                }
                                
                                $subscription = $shop->activeSubscription();
                                if (!$subscription) {
                                    return 'No active subscription';
                                }
                                
                                return ucfirst($subscription->status);
                            }),
                        
                        Forms\Components\Placeholder::make('start_date')
                            ->label('Start Date')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'N/A';
                                }
                                
                                $subscription = $shop->activeSubscription();
                                if (!$subscription) {
                                    return 'N/A';
                                }
                                
                                return $subscription->start_date->format('M d, Y');
                            }),
                        
                        Forms\Components\Placeholder::make('end_date')
                            ->label('End Date')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'N/A';
                                }
                                
                                $subscription = $shop->activeSubscription();
                                if (!$subscription) {
                                    return 'N/A';
                                }
                                
                                return $subscription->end_date->format('M d, Y');
                            }),
                        
                        Forms\Components\Placeholder::make('days_remaining')
                            ->label('Days Remaining')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'N/A';
                                }
                                
                                $subscription = $shop->activeSubscription();
                                if (!$subscription) {
                                    return 'N/A';
                                }
                                
                                return $subscription->end_date->diffInDays(now()) . ' days';
                            }),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Available Features')
                    ->schema([
                        Forms\Components\Placeholder::make('features')
                            ->label('Features in your plan')
                            ->content(function () {
                                $shop = Auth::user()->shop;
                                if (!$shop) {
                                    return 'No shop associated with your account.';
                                }
                                
                                $subscription = $shop->activeSubscription();
                                if (!$subscription) {
                                    return 'No active subscription.';
                                }
                                
                                $features = $subscription->plan->features;
                                if (empty($features)) {
                                    return 'No features defined for this plan.';
                                }
                                
                                $featuresList = '';
                                foreach ($features as $feature => $enabled) {
                                    if ($enabled) {
                                        $featuresList .= '• ' . ucfirst(str_replace('_', ' ', $feature)) . "\n";
                                    }
                                }
                                
                                return $featuresList;
                            }),
                    ]),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('renew')
                ->label('Renew Subscription')
                ->url(route('subscriptions.current'))
                ->color('success')
                ->visible(function () {
                    $shop = Auth::user()->shop;
                    return $shop && $shop->activeSubscription();
                }),
            
            Action::make('change_plan')
                ->label('Change Plan')
                ->url(route('subscriptions.plans'))
                ->color('warning')
                ->visible(function () {
                    $shop = Auth::user()->shop;
                    return $shop && $shop->activeSubscription() && !$shop->pendingSubscription();
                }),
            
            Action::make('pending_info')
                ->label('Pending Approval')
                ->color('secondary')
                ->icon('heroicon-o-clock')
                ->disabled()
                ->tooltip('Your subscription request is pending admin approval')
                ->visible(function () {
                    $shop = Auth::user()->shop;
                    return $shop && $shop->pendingSubscription();
                }),
            
            Action::make('subscribe')
                ->label('Subscribe to a Plan')
                ->url(route('subscriptions.plans'))
                ->color('primary')
                ->visible(function () {
                    $shop = Auth::user()->shop;
                    return $shop && !$shop->activeSubscription() && !$shop->pendingSubscription();
                }),
        ];
    }
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('shop_owner');
    }
}

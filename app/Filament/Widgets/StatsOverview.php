<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Total Projects
        $totalProjects = Project::count();

        // Total Tickets
        $totalTickets = Ticket::count();

        // Tickets created in the last 7 days
        $newTicketsLastWeek = Ticket::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Users count
        $usersCount = User::count();

        // FIXED: Tickets without assignees (using new many-to-many relationship)
        $unassignedTickets = Ticket::whereDoesntHave('assignees')->count();

        // My assigned tickets (for current user)
        $myTickets = Ticket::whereHas('assignees', function ($query) {
            $query->where('users.id', auth()->id());
        })->count();

        // Tickets created by current user
        $myCreatedTickets = Ticket::where('created_by', auth()->id())->count();

        // Overdue tickets
        $overdueTickets = Ticket::where('due_date', '<', Carbon::now())
            ->whereHas('status', function ($query) {
                // Assuming you have status names like 'completed', 'done', etc.
                $query->whereNotIn('name', ['Completed', 'Done', 'Closed']);
            })
            ->count();

        return [
            Stat::make('Total Projects', $totalProjects)
                ->description('Active projects in the system')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('Total Tickets', $totalTickets)
                ->description('Tickets across all projects')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),

            Stat::make('My Assigned Tickets', $myTickets)
                ->description('Tickets assigned to you')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('info'),

            Stat::make('New Tickets This Week', $newTicketsLastWeek)
                ->description('Created in the last 7 days')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),

            Stat::make('Unassigned Tickets', $unassignedTickets)
                ->description('Tickets without any assignee')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($unassignedTickets > 0 ? 'danger' : 'success'),

            Stat::make('My Created Tickets', $myCreatedTickets)
                ->description('Tickets you created')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),

            Stat::make('Overdue Tickets', $overdueTickets)
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueTickets > 0 ? 'danger' : 'success'),

            Stat::make('Team Members', $usersCount)
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
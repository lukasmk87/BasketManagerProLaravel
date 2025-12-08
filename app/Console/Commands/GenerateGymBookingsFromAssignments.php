<?php

namespace App\Console\Commands;

use App\Models\GymTimeSlotTeamAssignment;
use App\Models\User;
use App\Services\Gym\GymTimeSlotAssignmentService;
use Illuminate\Console\Command;

class GenerateGymBookingsFromAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gym:generate-bookings
                            {--assignment= : ID einer bestimmten Zuordnung}
                            {--dry-run : Zeigt nur an, wie viele Buchungen erstellt würden}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generiert Buchungen aus Team-Zuordnungen (bis Saison-Ende)';

    /**
     * Execute the console command.
     */
    public function handle(GymTimeSlotAssignmentService $service): int
    {
        $assignmentId = $this->option('assignment');
        $dryRun = $this->option('dry-run');

        $query = GymTimeSlotTeamAssignment::where('status', 'active')
            ->with(['gymTimeSlot.gymHall', 'team']);

        if ($assignmentId) {
            $query->where('id', $assignmentId);
        }

        $assignments = $query->get();

        if ($assignments->isEmpty()) {
            $this->warn('Keine aktiven Team-Zuordnungen gefunden.');

            return 0;
        }

        $this->info("Verarbeite {$assignments->count()} Zuordnung(en)...");

        if ($dryRun) {
            $this->warn('DRY-RUN Modus - Es werden keine Buchungen erstellt.');
        }

        // System-User für die Buchungserstellung
        $systemUser = User::whereHas('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->first();

        if (! $systemUser) {
            $systemUser = User::first();
        }

        if (! $systemUser) {
            $this->error('Kein Benutzer für Buchungserstellung gefunden.');

            return 1;
        }

        $total = 0;
        $this->newLine();

        foreach ($assignments as $assignment) {
            $hallName = $assignment->gymTimeSlot?->gymHall?->name ?? 'Unbekannt';
            $teamName = $assignment->team?->name ?? 'Unbekannt';
            $dayName = $this->translateDay($assignment->day_of_week);

            $this->line("Zuordnung #{$assignment->id}: {$teamName} - {$dayName} {$assignment->start_time}-{$assignment->end_time} ({$hallName})");

            if ($dryRun) {
                // Im Dry-Run Modus zählen wir nur die zu erstellenden Buchungen
                $count = $this->countPotentialBookings($assignment, $service);
                $this->info("  → Würde {$count} Buchungen erstellen");
                $total += $count;
            } else {
                $created = $service->generateBookingsForAssignment($assignment, $systemUser);
                $this->info("  → {$created} Buchungen erstellt");
                $total += $created;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Gesamt: {$total} Buchungen würden erstellt werden.");
        } else {
            $this->info("Gesamt: {$total} Buchungen erstellt.");
        }

        return 0;
    }

    /**
     * Zählt die potenziellen Buchungen für eine Zuordnung.
     */
    protected function countPotentialBookings(GymTimeSlotTeamAssignment $assignment, GymTimeSlotAssignmentService $service): int
    {
        // Verwende Reflection um die protected Methoden zu nutzen
        $startDate = now()->startOfWeek();
        $endDate = $this->invokeProtectedMethod($service, 'getSeasonEndDate', [$assignment]);

        if (! $endDate) {
            $endDate = now()->addWeeks(12)->endOfWeek();
        }

        $count = 0;
        $current = $startDate->copy();
        $dayOfWeek = $assignment->day_of_week;

        while ($current->lte($endDate)) {
            if (strtolower($current->format('l')) === $dayOfWeek) {
                if ($this->invokeProtectedMethod($service, 'isDateInAssignmentRange', [$assignment, $current])) {
                    if (! $this->invokeProtectedMethod($service, 'hasBookingForDate', [$assignment, $current])) {
                        $count++;
                    }
                }
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Ruft eine protected Methode auf.
     */
    protected function invokeProtectedMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * Übersetzt englische Wochentage ins Deutsche.
     */
    protected function translateDay(string $day): string
    {
        return match ($day) {
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
            default => $day,
        };
    }
}

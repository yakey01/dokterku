<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\JadwalJaga;
use App\Filament\Resources\JadwalJagaResource;
use Illuminate\Database\Eloquent\Model;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = JadwalJaga::class;

    public function fetchEvents(array $fetchInfo): array
    {
        try {
            return JadwalJaga::query()
                ->with(['pegawai', 'shiftTemplate'])
                ->whereBetween('tanggal_jaga', [$fetchInfo['start'], $fetchInfo['end']])
                ->get()
                ->map(function (JadwalJaga $event) {
                    // Build title safely with null checks
                    $title = $event->pegawai?->name ?? 'Unknown Staff';
                    if ($event->shiftTemplate?->nama_shift) {
                        $title .= ' (' . $event->shiftTemplate->nama_shift . ')';
                    }
                    
                    // Build start/end times safely
                    $startTime = $event->shiftTemplate?->jam_masuk ?? '08:00';
                    $endTime = $event->shiftTemplate?->jam_pulang ?? '16:00';
                    
                    return [
                        'id' => $event->id,
                        'title' => $title,
                        'start' => $event->tanggal_jaga->format('Y-m-d') . 'T' . $startTime,
                        'end' => $event->tanggal_jaga->format('Y-m-d') . 'T' . $endTime,
                        'backgroundColor' => $event->shiftTemplate?->color ?? '#6b7280',
                        'borderColor' => $event->shiftTemplate?->color ?? '#6b7280',
                        'url' => JadwalJagaResource::getUrl(name: 'edit', parameters: ['record' => $event]),
                        'shouldOpenUrlInNewTab' => false
                    ];
                })
                ->all();
        } catch (\Exception $e) {
            \Log::error('CalendarWidget fetchEvents error: ' . $e->getMessage());
            return [];
        }
    }

    public function getViewData(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay'
            ],
            'height' => 'auto',
            'locale' => 'id',
            'firstDay' => 1, // Monday
            'displayEventTime' => true,
            'eventDisplay' => 'block',
            'dayMaxEvents' => 3,
            'moreLinkClick' => 'popover',
            'navLinks' => true,
            'selectable' => true,
            'selectMirror' => true,
        ];
    }

    protected function headerActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label('Tambah Jadwal')
                ->icon('heroicon-o-plus')
                ->url(JadwalJagaResource::getUrl('create'))
                ->button(),
        ];
    }

    public function eventDidMount(): string
    {
        return <<<JS
        function({ event, el }) {
            el.setAttribute("x-tooltip", `\${event.title} - \${event.extendedProps.description || ''}`);
        }
        JS;
    }
}
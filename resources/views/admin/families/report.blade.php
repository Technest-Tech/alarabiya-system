<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Family Billing Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 24px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #111827;
        }
        .header p {
            margin: 4px 0;
            color: #6b7280;
        }
        .family-info {
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            background-color: #f9fafb;
        }
        .family-info .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .family-info .label {
            font-weight: 600;
            color: #374151;
        }
        .student-section {
            margin-bottom: 32px;
        }
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
        }
        .student-header h2 {
            margin: 0;
            font-size: 16px;
            color: #111827;
        }
        .student-header p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
        }
        td {
            font-size: 12px;
        }
        .student-total {
            text-align: right;
            font-weight: 600;
            color: #111827;
            margin-top: 8px;
        }
        .totals {
            border-top: 2px solid #111827;
            padding-top: 12px;
        }
        .totals .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .totals .label {
            font-weight: 600;
            color: #111827;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 9999px;
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Family Billing Report</h1>
        <p>{{ $family->name }} &bullet; {{ $monthLabel }}</p>
    </div>

    <div class="family-info">
        <div class="row">
            <span class="label">Family Name:</span>
            <span>{{ $family->name }}</span>
        </div>
        <div class="row">
            <span class="label">WhatsApp:</span>
            <span>{{ $family->whatsapp_number ?? 'Not provided' }}</span>
        </div>
        <div class="row">
            <span class="label">Students:</span>
            <span>{{ $family->students->pluck('name')->implode(', ') }}</span>
        </div>
    </div>

    @foreach ($summary['students'] as $studentSummary)
        @php
            $student = $studentSummary['student'];
        @endphp
        <div class="student-section">
            <div class="student-header">
                <div>
                    <h2>{{ $student->name }}</h2>
                    <p>Teacher: {{ $student->teacher?->user?->name ?? 'Unassigned' }}</p>
                </div>
                <div>
                    <span class="badge">Total</span>
                    {{ $studentSummary['currency'] }} {{ number_format($studentSummary['total'], 2) }}
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Teacher</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Hourly Rate</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($studentSummary['lesson_rows'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['teacher'] ?? '—' }}</td>
                            <td>{{ $row['duration_label'] }}</td>
                            <td>
                                @php
                                    $statusLabels = [
                                        'attended' => 'Attended',
                                        'absent_student' => 'Absent Student',
                                        'absent_teacher' => 'Absent Teacher',
                                        'cancelled_student' => 'Cancelled Student',
                                        'cancelled_teacher' => 'Cancelled Teacher',
                                        'trial' => 'Trial',
                                    ];
                                @endphp
                                {{ $statusLabels[$row['status']] ?? ucfirst($row['status']) }}
                            </td>
                            <td>{{ $studentSummary['currency'] }} {{ number_format($row['hourly_rate'], 2) }}</td>
                            <td>{{ $studentSummary['currency'] }} {{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #6b7280; padding: 16px;">
                                No lessons recorded for this month.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($studentSummary['manual_entries']->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th colspan="2">Manual Adjustments</th>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($studentSummary['manual_entries'] as $entry)
                            <tr>
                                <td>{{ $entry['description'] ?? 'Manual adjustment' }}</td>
                                <td>{{ $studentSummary['currency'] }} {{ number_format($entry['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td style="text-align: right; font-weight: 600;">Manual Total</td>
                            <td style="font-weight: 600;">{{ $studentSummary['currency'] }} {{ number_format($studentSummary['manual_total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            <div class="student-total">
                Student Total: {{ $studentSummary['currency'] }} {{ number_format($studentSummary['total'], 2) }}
            </div>
        </div>
    @endforeach

    <div class="totals">
        @foreach ($summary['currencyTotals'] as $currency => $amount)
            <div class="row">
                <span class="label">Total ({{ $currency }})</span>
                <span>{{ number_format($amount, 2) }}</span>
            </div>
        @endforeach
    </div>
</body>
</html>



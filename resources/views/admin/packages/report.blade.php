<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Monthly Report</title>
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
        .summary {
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            background-color: #f9fafb;
        }
        .summary .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .summary .label {
            font-weight: 600;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
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
        .totals {
            margin-top: 16px;
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
            background-color: #e0e7ff;
            color: #3730a3;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .manual-section {
            margin-top: 24px;
        }
        .manual-section h2 {
            font-size: 14px;
            margin-bottom: 8px;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Monthly Report</h1>
        <p>{{ $student->name }} &bullet; {{ $monthLabel }}</p>
    </div>

    <div class="summary">
        <div class="row">
            <span class="label">Student:</span>
            <span>{{ $student->name }}</span>
        </div>
        <div class="row">
            <span class="label">Teacher:</span>
            <span>{{ optional($student->teacher->user)->name ?? 'Unassigned' }}</span>
        </div>
        <div class="row">
            <span class="label">Currency:</span>
            <span>{{ strtoupper($currency) }}</span>
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
            @forelse ($lessonRows as $row)
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
                    <td>{{ $currency }} {{ number_format($row['hourly_rate'], 2) }}</td>
                    <td>{{ $currency }} {{ number_format($row['amount'], 2) }}</td>
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

    <div class="totals">
        <div class="row">
            <span class="label">Lessons Total</span>
            <span>{{ $currency }} {{ number_format($lessonTotal, 2) }}</span>
        </div>
    </div>

    @if ($manualEntries->isNotEmpty())
        <div class="manual-section">
            <h2>Manual Adjustments <span class="badge">Manual</span></h2>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($manualEntries as $entry)
                        <tr>
                            <td>{{ $entry['description'] ?? 'Manual adjustment' }}</td>
                            <td>{{ $currency }} {{ number_format($entry['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="text-align: right; font-weight: 600;">Manual Total</td>
                        <td style="font-weight: 600;">{{ $currency }} {{ number_format($manualTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div class="totals">
        <div class="row" style="font-size: 14px;">
            <span class="label">Grand Total</span>
            <span>{{ $currency }} {{ number_format($grandTotal, 2) }}</span>
        </div>
    </div>
</body>
</html>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Report - {{ $billing->student->name }} - {{ $billing->month_label }}</title>
    <style>
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .student-info { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 16px 12px; text-align: left; line-height: 1.5; }
        .table th { background-color: #f4f4f4; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Billing Report</h2>
        <p>{{ $billing->month_label }}</p>
    </div>

    <div class="student-info">
        <table style="width: 100%;">
            <tr>
                <td><strong>Student Name:</strong> {{ $billing->student->name }}</td>
                <td class="text-right"><strong>Status:</strong> {{ ucfirst($billing->status) }}</td>
            </tr>
            <tr>
                <td><strong>Hourly Rate:</strong> {{ number_format($billing->student->hourly_rate, 2) }} {{ $billing->currency }}</td>
                <td class="text-right"><strong>Type:</strong> {{ ucfirst($billing->type) }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description / Teacher</th>
                <th>Duration (Mins)</th>
                <th>Hourly Rate</th>
                <th class="text-right">Amount ({{ $billing->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($billing->items as $item)
                <tr>
                    <td>{{ $item->lesson ? \Carbon\Carbon::parse($item->lesson->date)->format('Y-m-d') : '-' }}</td>
                    <td>
                        @if($item->lesson)
                            Lesson with {{ $item->lesson->teacher ? $item->lesson->teacher->name : 'N/A' }}
                        @else
                            {{ $item->description }}
                        @endif
                    </td>
                    <td>{{ $item->duration_minutes }}</td>
                    <td>{{ number_format($item->hourly_rate ?? $billing->student->hourly_rate, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-right">Total:</td>
                <td>{{ $billing->items->sum('duration_minutes') }} mins ({{ round($billing->items->sum('duration_minutes') / 60, 2) }} hrs)</td>
                <td></td>
                <td class="text-right">{{ number_format($billing->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

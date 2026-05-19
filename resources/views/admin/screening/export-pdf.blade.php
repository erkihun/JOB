<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<style>
    @page { size: A4 landscape; margin: 0; }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Ebrima, DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937;
           padding: 14mm 20mm 12mm 20mm; }

    /* ── Header ─────────────────────────────────────────────── */
    .header { border-bottom: 2.5px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 12px; }
    .header-wrap  { display: table; width: 100%; }
    .header-left  { display: table-cell; vertical-align: top; }
    .header-right { display: table-cell; vertical-align: top; text-align: right; white-space: nowrap; }
    .org-name     { font-size: 13px; font-weight: bold; color: #1d4ed8; }
    .report-title { font-size: 10px; font-weight: bold; color: #374151; margin-top: 3px; }
    .report-meta  { font-size: 8px; color: #6b7280; margin-top: 4px; }
    .report-date  { font-size: 8px; color: #6b7280; }
    .filter-note  { display: inline-block; margin-top: 5px; font-size: 8px; color: #92400e;
                    background: #fef3c7; padding: 2px 6px; border-radius: 3px; }

    /* ── Table ───────────────────────────────────────────────── */
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }

    thead tr { background-color: #1d4ed8; }
    thead th { color: #ffffff; padding: 6px 7px; text-align: left;
               font-family: Ebrima, DejaVu Sans, sans-serif;
               font-size: 7.5px; font-weight: bold;
               text-transform: uppercase; letter-spacing: 0.03em; }

    tbody tr:nth-child(even) { background-color: #eff6ff; }
    tbody tr:nth-child(odd)  { background-color: #ffffff; }
    tbody td { padding: 6px 7px; border-bottom: 1px solid #e5e7eb; vertical-align: top;
               font-family: Ebrima, DejaVu Sans, sans-serif; }

    .mono  { font-size: 7.5px; color: #3b82f6; }
    .muted { color: #9ca3af; font-size: 7.5px; }
    .code  { font-size: 7px; color: #9ca3af; margin-top: 1px; }

    .badge        { display: inline-block; padding: 1px 5px; border-radius: 3px;
                    font-size: 7.5px; font-weight: bold; }
    .badge-pass   { background: #dcfce7; color: #166534; }
    .badge-fail   { background: #fee2e2; color: #991b1b; }

    /* ── Footer ──────────────────────────────────────────────── */
    .footer       { margin-top: 10px; border-top: 1px solid #e5e7eb; padding-top: 5px;
                    display: table; width: 100%; }
    .footer-left  { display: table-cell; font-size: 7.5px; color: #9ca3af; }
    .footer-right { display: table-cell; text-align: right; font-size: 7.5px; color: #9ca3af; }
</style>
</head>
<body>

@php $orgName = \App\Models\Setting::get('org.name', '') ?: config('app.name'); @endphp

<div class="header">
    <div class="header-wrap">
        <div class="header-left">
            <div class="org-name">{{ $orgName }}</div>
            <div class="report-title">{{ $title }}</div>
            <div class="report-meta">
                {{ __('messages.generated') }}: {{ now()->format('d M Y, H:i') }}
                &nbsp;&nbsp;|&nbsp;&nbsp;
                {{ __('messages.total_records') }}: {{ $applications->count() }}
            </div>
            @if($vacancyFilter)
                <div class="filter-note">{{ __('menus.vacancies') }}: {{ $vacancyFilter }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="report-date">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
</div>

<table>
    <colgroup>
        <col style="width:2%">
        <col style="width:10%">
        <col style="width:12%">
        <col style="width:6%">
        <col style="width:14%">
        <col style="width:13%">
        <col style="width:11%">
        <col style="width:11%">
        <col style="width:10%">
        <col style="width:6%">
        <col style="width:5%">
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('messages.applicant_code') }}</th>
            <th>{{ __('fields.full_name') }}</th>
            <th>{{ __('fields.gender') }}</th>
            <th>{{ __('menus.vacancies') }}</th>
            <th>{{ __('messages.vacancy_qualification') }}</th>
            <th>{{ __('fields.education_level') }}</th>
            <th>{{ __('fields.field_of_study') }}</th>
            <th>{{ __('vacancies.status') }}</th>
            <th>{{ __('messages.screened_by') }}</th>
            <th>{{ __('messages.screened_date') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($applications as $i => $app)
            @php
                $vacQual = collect([
                    $app->vacancy?->field_of_study,
                    $app->vacancy?->minimum_experience !== null
                        ? $app->vacancy->minimum_experience.' yrs exp'
                        : null,
                ])->filter()->implode(' · ');
            @endphp
            <tr>
                <td class="muted">{{ $i + 1 }}</td>
                <td class="mono">{{ $app->applicant?->applicant_code ?? '--' }}</td>
                <td>{{ $app->applicant?->full_name ?? '--' }}</td>
                <td>{{ $app->applicant?->gender?->getLabel() ?? '--' }}</td>
                <td>
                    {{ $app->vacancy?->title ?? '--' }}
                    <div class="code">{{ $app->vacancy?->code }}</div>
                </td>
                <td>{{ $vacQual ?: '--' }}</td>
                <td>{{ $app->applicant?->education_level?->getLabel() ?? '--' }}</td>
                <td>{{ $app->applicant?->field_of_study ?? '--' }}</td>
                <td>
                    @if($app->status->value === 'passed_screening')
                        <span class="badge badge-pass">{{ __('messages.pass') }}</span>
                    @else
                        <span class="badge badge-fail">{{ __('messages.fail') }}</span>
                    @endif
                </td>
                <td>{{ $app->screener?->name ?? '--' }}</td>
                <td class="muted">{{ $app->screened_at?->format('d M Y') ?? '--' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" style="text-align:center;padding:16px;color:#9ca3af;">
                    {{ __('messages.no_records') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <div class="footer-left">{{ $orgName }} &mdash; Confidential</div>
    <div class="footer-right">{{ now()->format('d M Y') }}</div>
</div>

</body>
</html>

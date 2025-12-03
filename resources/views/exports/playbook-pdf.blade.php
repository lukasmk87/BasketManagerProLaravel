<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $playbook->name }} - Playbook</title>
    <style>
        @page {
            margin: 20mm 15mm 25mm 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            background: #fff;
            counter-reset: page-counter;
        }
        .cover-page {
            text-align: center;
            padding: 60px 40px;
            min-height: 90vh;
        }
        .cover-title {
            font-size: 36px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 15px;
        }
        .cover-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .cover-badge {
            display: inline-block;
            padding: 8px 24px;
            background-color: #e25822;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 40px;
        }
        .cover-meta {
            margin-top: 40px;
            font-size: 12px;
            color: #666;
            line-height: 2;
        }
        .cover-meta strong {
            color: #444;
        }
        .cover-stats {
            margin-top: 50px;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            display: inline-block;
        }
        .stats-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stats-grid {
            display: table;
            width: 100%;
        }
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 0 25px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #e25822;
        }
        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .page-break {
            page-break-before: always;
        }
        .toc {
            padding: 30px 20px;
        }
        .toc-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #e25822;
        }
        .toc-list {
            list-style: none;
        }
        .toc-item {
            display: table;
            width: 100%;
            padding: 12px 0;
            border-bottom: 1px dotted #ddd;
        }
        .toc-number {
            display: table-cell;
            width: 40px;
            font-weight: bold;
            color: #e25822;
            font-size: 14px;
            vertical-align: middle;
        }
        .toc-content {
            display: table-cell;
            vertical-align: middle;
        }
        .toc-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }
        .toc-category {
            font-size: 10px;
            color: #888;
            margin-top: 2px;
        }
        .play-page {
            page-break-before: always;
            padding: 20px 0;
        }
        .play-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #e25822;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .play-header-left {
            display: table-cell;
            vertical-align: middle;
        }
        .play-header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 100px;
        }
        .play-number {
            display: inline-block;
            width: 36px;
            height: 36px;
            line-height: 36px;
            text-align: center;
            background-color: #e25822;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 16px;
            margin-right: 12px;
            vertical-align: middle;
        }
        .play-title {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
            vertical-align: middle;
        }
        .play-meta {
            margin-top: 8px;
            padding-left: 50px;
        }
        .play-category-badge {
            display: inline-block;
            padding: 3px 10px;
            background-color: #f0f0f0;
            color: #666;
            border-radius: 10px;
            font-size: 10px;
            margin-right: 8px;
        }
        .play-court-badge {
            display: inline-block;
            padding: 3px 10px;
            background-color: #e9ecef;
            color: #555;
            border-radius: 10px;
            font-size: 10px;
        }
        .play-diagram {
            text-align: center;
            margin: 25px 0;
            page-break-inside: avoid;
        }
        .diagram-wrapper {
            display: inline-block;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .play-diagram img {
            max-width: 100%;
            max-height: 340px;
            border-radius: 8px;
            display: block;
        }
        .no-diagram {
            padding: 80px 40px;
            background-color: #f8f9fa;
            color: #999;
            text-align: center;
            border-radius: 12px;
            font-size: 13px;
        }
        .play-description {
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #e25822;
            margin-top: 20px;
        }
        .play-description-title {
            font-size: 12px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .play-description p {
            color: #444;
            line-height: 1.7;
        }
        .play-notes {
            margin-top: 15px;
            padding: 12px 16px;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 0 8px 8px 0;
        }
        .play-notes-title {
            font-size: 11px;
            font-weight: bold;
            color: #856404;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .play-notes p {
            color: #856404;
            font-size: 11px;
            line-height: 1.6;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 15mm;
            background-color: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            font-size: 9px;
            color: #666;
        }
        .footer-content {
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            text-align: left;
        }
        .footer-center {
            display: table-cell;
            text-align: center;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
        }
        .brand {
            color: #e25822;
            font-weight: bold;
        }
        .page-indicator {
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Cover Page -->
    <div class="cover-page">
        <div class="cover-title">{{ $playbook->name }}</div>
        @if($playbook->description)
            <div class="cover-subtitle">{{ $playbook->description }}</div>
        @endif
        <div class="cover-badge">{{ $playbook->category_display }}</div>

        <div class="cover-meta">
            @if($team)
                <div><strong>Team:</strong> {{ $team->name }}</div>
            @endif
            <div><strong>Erstellt von:</strong> {{ $createdBy->name ?? 'Unbekannt' }}</div>
            <div><strong>Exportiert:</strong> {{ $exportDate }}</div>
            @if($playbook->is_default)
                <div><strong>Standard-Playbook</strong></div>
            @endif
        </div>

        <div class="cover-stats">
            <div class="stats-title">Übersicht</div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">{{ $statistics['total_plays'] }}</div>
                    <div class="stat-label">Spielzüge</div>
                </div>
                @foreach($statistics['plays_by_category'] as $category => $count)
                    @if($count > 0)
                        <div class="stat-item">
                            <div class="stat-value">{{ $count }}</div>
                            <div class="stat-label">{{ ucfirst($category) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Table of Contents -->
    <div class="page-break toc">
        <div class="toc-title">Inhaltsverzeichnis</div>
        <ul class="toc-list">
            @foreach($plays as $index => $play)
                <li class="toc-item">
                    <span class="toc-number">{{ $index + 1 }}</span>
                    <span class="toc-content">
                        <span class="toc-name">{{ $play->name }}</span>
                        <div class="toc-category">{{ $play->category_display }} | {{ $play->court_type_display }}</div>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Individual Plays -->
    @foreach($plays as $index => $play)
        <div class="play-page">
            <div class="play-header">
                <div class="play-header-left">
                    <span class="play-number">{{ $index + 1 }}</span>
                    <span class="play-title">{{ $play->name }}</span>
                    <div class="play-meta">
                        <span class="play-category-badge">{{ $play->category_display }}</span>
                        <span class="play-court-badge">{{ $play->court_type_display }}</span>
                    </div>
                </div>
                <div class="play-header-right">
                    <span style="font-size: 10px; color: #888;">Spielzug {{ $index + 1 }}/{{ count($plays) }}</span>
                </div>
            </div>

            <div class="play-diagram">
                @if(isset($thumbnails[$play->id]))
                    <div class="diagram-wrapper">
                        <img src="{{ $thumbnails[$play->id] }}" alt="{{ $play->name }}">
                    </div>
                @elseif($play->thumbnail_path)
                    <div class="diagram-wrapper">
                        <img src="{{ Storage::url($play->thumbnail_path) }}" alt="{{ $play->name }}">
                    </div>
                @else
                    <div class="no-diagram">
                        <strong>Kein Diagramm verfügbar</strong><br>
                        <small>Erstellen Sie ein Diagramm im Taktik-Board-Editor</small>
                    </div>
                @endif
            </div>

            @if($play->description)
                <div class="play-description">
                    <div class="play-description-title">Beschreibung</div>
                    <p>{{ $play->description }}</p>
                </div>
            @endif

            @if($play->pivot && $play->pivot->notes)
                <div class="play-notes">
                    <div class="play-notes-title">Playbook-Notizen</div>
                    <p>{{ $play->pivot->notes }}</p>
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <span class="brand">{{ config('app.name') }}</span>
            </div>
            <div class="footer-center">
                {{ $playbook->name }}
            </div>
            <div class="footer-right">
                {{ $exportDate }}
            </div>
        </div>
    </div>
</body>
</html>

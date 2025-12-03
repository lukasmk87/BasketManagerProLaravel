<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $play->name }} - Spielzug</title>
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
        }
        .page-container {
            position: relative;
            min-height: 100%;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #e25822;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
        }
        .title {
            font-size: 26px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .category-badge {
            display: inline-block;
            padding: 5px 14px;
            background-color: #e25822;
            color: white;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-info {
            font-size: 10px;
            color: #666;
            line-height: 1.8;
        }
        .meta-info strong {
            color: #444;
        }
        .court-type-badge {
            display: inline-block;
            padding: 3px 10px;
            background-color: #f0f0f0;
            color: #666;
            border-radius: 10px;
            font-size: 10px;
            margin-top: 5px;
        }
        .diagram-section {
            text-align: center;
            margin: 25px 0;
            page-break-inside: avoid;
        }
        .diagram-container {
            display: inline-block;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .diagram-container img {
            max-width: 100%;
            max-height: 380px;
            border-radius: 8px;
            display: block;
        }
        .no-diagram {
            padding: 100px 50px;
            background-color: #f8f9fa;
            color: #999;
            text-align: center;
            border-radius: 12px;
            font-size: 14px;
        }
        .content-section {
            margin: 20px 0;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #e25822;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #f0f0f0;
        }
        .description-box {
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #e25822;
        }
        .description-box p {
            color: #444;
            line-height: 1.7;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .info-table tr {
            border-bottom: 1px solid #eee;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        .info-table td:first-child {
            width: 140px;
            font-weight: 600;
            color: #555;
            background-color: #f9f9f9;
        }
        .info-table td:last-child {
            color: #333;
        }
        .status-published {
            display: inline-block;
            padding: 3px 10px;
            background-color: #28a745;
            color: white;
            border-radius: 10px;
            font-size: 10px;
        }
        .status-draft {
            display: inline-block;
            padding: 3px 10px;
            background-color: #ffc107;
            color: #333;
            border-radius: 10px;
            font-size: 10px;
        }
        .status-archived {
            display: inline-block;
            padding: 3px 10px;
            background-color: #6c757d;
            color: white;
            border-radius: 10px;
            font-size: 10px;
        }
        .tags-container {
            margin-top: 15px;
        }
        .tag {
            display: inline-block;
            padding: 4px 12px;
            background-color: #e9ecef;
            color: #495057;
            border-radius: 12px;
            font-size: 10px;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .animation-info {
            display: inline-block;
            padding: 4px 12px;
            background-color: #17a2b8;
            color: white;
            border-radius: 10px;
            font-size: 10px;
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
        .footer-right {
            display: table-cell;
            text-align: right;
        }
        .brand {
            color: #e25822;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header">
            <div class="header-left">
                <div class="title">{{ $play->name }}</div>
                <span class="category-badge">{{ $play->category_display }}</span>
                <span class="court-type-badge">{{ $play->court_type_display }}</span>
            </div>
            <div class="header-right">
                <div class="meta-info">
                    <strong>Erstellt von:</strong> {{ $createdBy->name ?? 'Unbekannt' }}<br>
                    <strong>Exportiert:</strong> {{ $exportDate }}<br>
                    @if($play->has_animation)
                        <span class="animation-info">Animation: {{ number_format($play->getAnimationDuration() / 1000, 1) }}s</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="diagram-section">
            @if($thumbnail)
                <div class="diagram-container">
                    <img src="{{ $thumbnail }}" alt="{{ $play->name }}">
                </div>
            @else
                <div class="no-diagram">
                    <strong>Kein Diagramm verfügbar</strong><br>
                    <small>Erstellen Sie ein Diagramm im Taktik-Board-Editor</small>
                </div>
            @endif
        </div>

        @if($play->description)
            <div class="content-section">
                <div class="section-title">Beschreibung</div>
                <div class="description-box">
                    <p>{{ $play->description }}</p>
                </div>
            </div>
        @endif

        <div class="content-section">
            <div class="section-title">Details</div>
            <table class="info-table">
                <tr>
                    <td>Kategorie</td>
                    <td>{{ $play->category_display }}</td>
                </tr>
                <tr>
                    <td>Spielfeld-Typ</td>
                    <td>{{ $play->court_type_display }}</td>
                </tr>
                <tr>
                    <td>Spieleranzahl</td>
                    <td>{{ $play->getPlayerCount() }} Spieler</td>
                </tr>
                @if($play->has_animation)
                    <tr>
                        <td>Animation</td>
                        <td>{{ number_format($play->getAnimationDuration() / 1000, 1) }} Sekunden</td>
                    </tr>
                @endif
                <tr>
                    <td>Status</td>
                    <td>
                        @if($play->status === 'published')
                            <span class="status-published">Veröffentlicht</span>
                        @elseif($play->status === 'draft')
                            <span class="status-draft">Entwurf</span>
                        @else
                            <span class="status-archived">Archiviert</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Nutzungszähler</td>
                    <td>{{ $play->usage_count ?? 0 }}x verwendet</td>
                </tr>
            </table>
        </div>

        @if($play->tags && count($play->tags) > 0)
            <div class="content-section">
                <div class="section-title">Tags</div>
                <div class="tags-container">
                    @foreach($play->tags as $tag)
                        <span class="tag">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="brand">{{ config('app.name') }}</span> - Taktik-Board Export
                </div>
                <div class="footer-right">
                    {{ $exportDate }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>

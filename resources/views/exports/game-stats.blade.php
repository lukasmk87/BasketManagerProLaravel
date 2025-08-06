<!DOCTYPE html>
<html>
<head>
    <title>Game Statistics</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 15px;
        }
        .game-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .game-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 5px;
        }
        .score-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .final-score {
            font-size: 36px;
            font-weight: bold;
            color: #1f2937;
            margin: 10px 0;
        }
        .team-name {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        .team-stats-container {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }
        .team-stats {
            width: 48%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
        }
        .team-stats h3 {
            margin-top: 0;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .stat-row:last-child {
            border-bottom: none;
        }
        .stat-label {
            font-weight: bold;
        }
        .stat-value {
            text-align: right;
        }
        .players-section {
            margin-top: 40px;
        }
        .players-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
        }
        .players-table th,
        .players-table td {
            border: 1px solid #d1d5db;
            padding: 6px 4px;
            text-align: center;
        }
        .players-table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        .players-table .player-name {
            text-align: left;
            max-width: 120px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin: 25px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
        }
        .game-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .game-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="game-title">{{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }}</div>
        <div class="game-subtitle">
            {{ $game->scheduled_at->format('d.m.Y H:i') }} - {{ $game->venue }}
            <br>
            {{ ucfirst($game->type) }} • {{ $game->season }}
        </div>
    </div>

    @if($game->status === 'finished')
    <div class="score-section">
        <div style="display: flex; justify-content: space-around; align-items: center;">
            <div>
                <div class="team-name">{{ $game->homeTeam->name }}</div>
                <div class="final-score">{{ $homeStats['final_score'] ?? $game->home_team_score }}</div>
                <div style="color: #6b7280;">Heim</div>
            </div>
            <div style="font-size: 24px; color: #6b7280;">VS</div>
            <div>
                <div class="team-name">{{ $game->awayTeam->name }}</div>
                <div class="final-score">{{ $awayStats['final_score'] ?? $game->away_team_score }}</div>
                <div style="color: #6b7280;">Gast</div>
            </div>
        </div>
        
        @if(isset($homeStats['is_win']) && $homeStats['is_win'])
            <div style="margin-top: 15px; color: #059669; font-weight: bold;">
                🏆 {{ $game->homeTeam->name }} gewinnt mit {{ abs($homeStats['margin'] ?? 0) }} Punkten
            </div>
        @elseif(isset($awayStats['is_win']) && $awayStats['is_win'])
            <div style="margin-top: 15px; color: #059669; font-weight: bold;">
                🏆 {{ $game->awayTeam->name }} gewinnt mit {{ abs($awayStats['margin'] ?? 0) }} Punkten
            </div>
        @endif
    </div>
    @endif

    <div class="game-info">
        <h3 style="margin-top: 0;">Spiel-Informationen</h3>
        <div class="game-info-grid">
            <div class="info-item">
                <span><strong>Datum:</strong></span>
                <span>{{ $game->scheduled_at->format('d.m.Y H:i') }}</span>
            </div>
            <div class="info-item">
                <span><strong>Spielort:</strong></span>
                <span>{{ $game->venue }}</span>
            </div>
            <div class="info-item">
                <span><strong>Status:</strong></span>
                <span>{{ ucfirst($game->status) }}</span>
            </div>
            <div class="info-item">
                <span><strong>Saison:</strong></span>
                <span>{{ $game->season }}</span>
            </div>
            @if($game->duration_minutes)
            <div class="info-item">
                <span><strong>Spielzeit:</strong></span>
                <span>{{ $game->duration_minutes }} Minuten</span>
            </div>
            @endif
            @if($game->attendance)
            <div class="info-item">
                <span><strong>Zuschauer:</strong></span>
                <span>{{ number_format($game->attendance, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="team-stats-container" style="display: block;">
        <div class="section-title">Team-Statistiken</div>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <tr>
                <th style="border: 1px solid #d1d5db; padding: 10px; background-color: #f9fafb; text-align: left;">Team</th>
                <th style="border: 1px solid #d1d5db; padding: 10px; background-color: #f9fafb; text-align: center;">{{ $game->homeTeam->name }}</th>
                <th style="border: 1px solid #d1d5db; padding: 10px; background-color: #f9fafb; text-align: center;">{{ $game->awayTeam->name }}</th>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Punkte</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['final_score'] ?? $game->home_team_score }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['final_score'] ?? $game->away_team_score }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Feldwürfe</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['field_goals_made'] ?? 0 }}/{{ $homeStats['field_goals_attempted'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['field_goals_made'] ?? 0 }}/{{ $awayStats['field_goals_attempted'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">3-Punkte</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['three_points_made'] ?? 0 }}/{{ $homeStats['three_points_attempted'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['three_points_made'] ?? 0 }}/{{ $awayStats['three_points_attempted'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Freiwürfe</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['free_throws_made'] ?? 0 }}/{{ $homeStats['free_throws_attempted'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['free_throws_made'] ?? 0 }}/{{ $awayStats['free_throws_attempted'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Rebounds</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['total_rebounds'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['total_rebounds'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Assists</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['assists'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['assists'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Steals</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['steals'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['steals'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Blocks</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['blocks'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['blocks'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Ballverluste</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['turnovers'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['turnovers'] ?? 0 }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 8px; font-weight: bold;">Fouls</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $homeStats['personal_fouls'] ?? 0 }}</td>
                <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">{{ $awayStats['personal_fouls'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    @if(count($homePlayerStats) > 0)
    <div class="players-section">
        <div class="section-title">{{ $game->homeTeam->name }} - Spielerstatistiken</div>
        <table class="players-table">
            <thead>
                <tr>
                    <th>Spieler</th>
                    <th>Pts</th>
                    <th>FGM/A</th>
                    <th>FG%</th>
                    <th>3PM/A</th>
                    <th>3P%</th>
                    <th>FTM/A</th>
                    <th>FT%</th>
                    <th>Reb</th>
                    <th>Ast</th>
                    <th>Stl</th>
                    <th>Blk</th>
                    <th>TO</th>
                    <th>Fouls</th>
                </tr>
            </thead>
            <tbody>
                @foreach($homePlayerStats as $playerStat)
                <tr>
                    <td class="player-name">{{ $playerStat['player']->name }}</td>
                    <td>{{ $playerStat['total_points'] }}</td>
                    <td>{{ $playerStat['field_goals_made'] }}/{{ $playerStat['field_goals_attempted'] }}</td>
                    <td>{{ $playerStat['field_goal_percentage'] }}%</td>
                    <td>{{ $playerStat['three_points_made'] }}/{{ $playerStat['three_points_attempted'] }}</td>
                    <td>{{ $playerStat['three_point_percentage'] }}%</td>
                    <td>{{ $playerStat['free_throws_made'] }}/{{ $playerStat['free_throws_attempted'] }}</td>
                    <td>{{ $playerStat['free_throw_percentage'] }}%</td>
                    <td>{{ $playerStat['total_rebounds'] }}</td>
                    <td>{{ $playerStat['assists'] }}</td>
                    <td>{{ $playerStat['steals'] }}</td>
                    <td>{{ $playerStat['blocks'] }}</td>
                    <td>{{ $playerStat['turnovers'] }}</td>
                    <td>{{ $playerStat['personal_fouls'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(count($awayPlayerStats) > 0)
    <div class="players-section">
        <div class="section-title">{{ $game->awayTeam->name }} - Spielerstatistiken</div>
        <table class="players-table">
            <thead>
                <tr>
                    <th>Spieler</th>
                    <th>Pts</th>
                    <th>FGM/A</th>
                    <th>FG%</th>
                    <th>3PM/A</th>
                    <th>3P%</th>
                    <th>FTM/A</th>
                    <th>FT%</th>
                    <th>Reb</th>
                    <th>Ast</th>
                    <th>Stl</th>
                    <th>Blk</th>
                    <th>TO</th>
                    <th>Fouls</th>
                </tr>
            </thead>
            <tbody>
                @foreach($awayPlayerStats as $playerStat)
                <tr>
                    <td class="player-name">{{ $playerStat['player']->name }}</td>
                    <td>{{ $playerStat['total_points'] }}</td>
                    <td>{{ $playerStat['field_goals_made'] }}/{{ $playerStat['field_goals_attempted'] }}</td>
                    <td>{{ $playerStat['field_goal_percentage'] }}%</td>
                    <td>{{ $playerStat['three_points_made'] }}/{{ $playerStat['three_points_attempted'] }}</td>
                    <td>{{ $playerStat['three_point_percentage'] }}%</td>
                    <td>{{ $playerStat['free_throws_made'] }}/{{ $playerStat['free_throws_attempted'] }}</td>
                    <td>{{ $playerStat['free_throw_percentage'] }}%</td>
                    <td>{{ $playerStat['total_rebounds'] }}</td>
                    <td>{{ $playerStat['assists'] }}</td>
                    <td>{{ $playerStat['steals'] }}</td>
                    <td>{{ $playerStat['blocks'] }}</td>
                    <td>{{ $playerStat['turnovers'] }}</td>
                    <td>{{ $playerStat['personal_fouls'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>
            Erstellt am {{ now()->format('d.m.Y H:i') }} | BasketManager Pro<br>
            <small>Dieses Dokument enthält offizielle Spielstatistiken</small>
        </p>
    </div>
</body>
</html>
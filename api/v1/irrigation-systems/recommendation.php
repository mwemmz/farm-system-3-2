<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $system = irrigationSystem($pdo, farmId($_GET['id'] ?? null), $user);
    $stmt = $pdo->prepare('SELECT id, date, rainfall_mm, temp_c, humidity, wind_kmh FROM weather_logs WHERE farm_id = ? AND date <= CURRENT_DATE AND (rainfall_mm IS NOT NULL OR temp_c IS NOT NULL) ORDER BY date DESC, id DESC LIMIT 1');
    $stmt->execute([$system['farm_id']]);
    $weather = $stmt->fetch() ?: null;
    $status = 'insufficient_weather_data';
    $advice = 'No recent usable weather record is available; check field conditions before changing irrigation.';
    $reasons = [];
    $suggestWeatherAdjusted = false;
    if ($weather) {
        $age = (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($weather['date']))->days;
        if ($age > 2) {
            $reasons[] = 'Latest farm weather record is older than two days.';
        } elseif ($weather['rainfall_mm'] !== null && (float)$weather['rainfall_mm'] >= 5) {
            $status = 'review_reduction';
            $advice = 'Recent recorded rainfall may allow irrigation to be delayed or reduced; check soil and field conditions first.';
            $reasons[] = 'Recent rainfall of ' . $weather['rainfall_mm'] . ' mm was recorded.';
            $suggestWeatherAdjusted = true;
        } elseif ($weather['rainfall_mm'] !== null && (float)$weather['rainfall_mm'] < 1 && $weather['temp_c'] !== null && (float)$weather['temp_c'] >= 30) {
            $status = 'review_demand';
            $advice = 'Hot and dry recorded conditions may increase irrigation demand; check soil and field conditions.';
            $reasons[] = 'Recent temperature was at least 30 C with less than 1 mm recorded rainfall.';
            $suggestWeatherAdjusted = true;
        } else {
            $status = 'keep_plan';
            $advice = 'Recorded weather does not indicate a clear adjustment; keep the plan unless field conditions suggest otherwise.';
            $reasons[] = 'Recent weather did not meet the advisory thresholds.';
        }
    } else {
        $reasons[] = 'No usable weather record exists for this farm.';
    }
    farmRespond(200, ['type'=>'rule_based_advisory','status'=>$status,'advisory'=>$advice,'basis'=>$reasons,'weather_record'=>$weather,'weather_adjusted_suggestion'=>$suggestWeatherAdjusted]);
});

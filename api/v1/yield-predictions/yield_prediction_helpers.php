<?php
require_once __DIR__ . '/../harvests/harvest_helpers.php';

const YIELD_DECIMAL_MAX = 99999999999.99; // Conservative bound for DECIMAL(15,4).

function yieldCrop($value) {
    $name = farmText($value, 'crop_name_snapshot', 255, true);
    return preg_replace('/\s+/u', ' ', $name);
}

function yieldKey($value) {
    return strtolower(yieldCrop($value));
}

function yieldDecimal($value, $name) {
    if (!is_finite($value) || $value < 0 || $value > YIELD_DECIMAL_MAX) {
        throw new FarmApiError(422, $name . ' exceeds supported DECIMAL range');
    }
    return round($value, 4);
}

function yieldHistory($pdo, $fieldId, $crop = null) {
    $stmt = $pdo->prepare("SELECT id,crop_name_snapshot,variety_snapshot,actual_harvest_date,actual_quantity_kg,loss_quantity_kg,area_harvested_hectares FROM harvests WHERE field_id = ? AND status = 'completed' AND actual_quantity_kg IS NOT NULL AND area_harvested_hectares > 0 ORDER BY actual_harvest_date DESC,id DESC");
    $stmt->execute([$fieldId]);
    $key = $crop === null ? null : yieldKey($crop);
    $samples = [];
    foreach ($stmt->fetchAll() as $row) {
        if ($key !== null && yieldKey($row['crop_name_snapshot']) !== $key) continue;
        $row['yield_kg_per_hectare'] = (float)$row['actual_quantity_kg'] / (float)$row['area_harvested_hectares'];
        $samples[] = $row;
    }
    return $samples;
}

function yieldStatistics($samples) {
    $values = array_map(fn($row) => $row['yield_kg_per_hectare'], $samples);
    $n = count($values);
    if ($n === 0) return null;
    $mean = array_sum($values) / $n;
    $variance = array_sum(array_map(fn($value) => ($value - $mean) ** 2, $values)) / $n;
    $stddev = sqrt($variance); // Population standard deviation of the observed harvests.
    $cv = $mean > 0 ? $stddev / $mean * 100 : null;
    return ['sample_count'=>$n,'mean_yield_kg_per_hectare'=>yieldDecimal($mean, 'Mean yield'),
        'min_yield_kg_per_hectare'=>yieldDecimal(min($values), 'Minimum yield'),
        'max_yield_kg_per_hectare'=>yieldDecimal(max($values), 'Maximum yield'),
        'standard_deviation'=>yieldDecimal($stddev, 'Standard deviation'),
        'coefficient_of_variation'=>$cv === null ? null : yieldDecimal($cv, 'Coefficient of variation')];
}

function yieldConfidence($stats, $samples) {
    $n = $stats['sample_count'];
    $values = array_column($samples, 'yield_kg_per_hectare');
    $mean = array_sum($values) / $n;
    $stddev = sqrt(array_sum(array_map(fn($value) => ($value - $mean) ** 2, $values)) / $n);
    $cv = $mean > 0 ? $stddev / $mean * 100 : null;
    if ($n === 2 || $cv === null || $cv > 30) return 'low';
    if ($n >= 5 && $cv <= 20) return 'high';
    return 'medium';
}

function yieldFactors($pdo, $field) {
    $fieldId = (int)$field['id'];
    $farmId = (int)$field['farm_id'];
    $soil = $pdo->prepare('SELECT soil_type,ph_level,moisture_percent,condition_notes,recorded_at FROM soil_records WHERE field_id = ? ORDER BY recorded_at DESC,id DESC LIMIT 1');
    $soil->execute([$fieldId]);
    $latestSoil = $soil->fetch() ?: null;

    $scout = $pdo->prepare("SELECT COUNT(*) AS recent_count,COALESCE(SUM(status IN ('open','monitoring')),0) AS unresolved_count,COALESCE(SUM(severity IN ('high','critical')),0) AS high_critical_count FROM scouting_reports WHERE field_id = ? AND observed_at >= NOW() - INTERVAL 30 DAY AND observed_at <= NOW()");
    $scout->execute([$fieldId]);
    $pest = $scout->fetch();

    // A null field_id denotes a farm-wide system; other fields' systems are excluded.
    $irrigation = $pdo->prepare("SELECT COUNT(DISTINCT s.id) AS system_count,COALESCE(SUM(CASE WHEN u.logged_at >= NOW() - INTERVAL 30 DAY AND u.logged_at <= NOW() THEN u.volume_liters ELSE 0 END),0) AS recent_water_liters,COALESCE(SUM(CASE WHEN u.logged_at >= NOW() - INTERVAL 30 DAY AND u.logged_at <= NOW() AND (u.issue_notes IS NOT NULL AND TRIM(u.issue_notes) <> '' OR u.pump_status IN ('maintenance','fault','offline')) THEN 1 ELSE 0 END),0) AS recent_issue_count FROM irrigation_systems s LEFT JOIN water_usage_logs u ON u.irrigation_system_id = s.id WHERE s.farm_id = ? AND (s.field_id = ? OR s.field_id IS NULL)");
    $irrigation->execute([$farmId,$fieldId]);
    $water = $irrigation->fetch();

    $weather = $pdo->prepare('SELECT date,rainfall_mm,temp_c,humidity,wind_kmh FROM weather_logs WHERE farm_id = ? AND date BETWEEN CURRENT_DATE - INTERVAL 30 DAY AND CURRENT_DATE ORDER BY date DESC,id DESC LIMIT 1');
    $weather->execute([$farmId]);
    $latestWeather = $weather->fetch() ?: null;

    return [
        'window_days'=>30,
        'soil'=>['record_available'=>$latestSoil !== null,'latest'=>$latestSoil],
        'pest_disease'=>['recent_scouting_count'=>(int)$pest['recent_count'],'unresolved_count'=>(int)$pest['unresolved_count'],'recent_high_critical_count'=>(int)$pest['high_critical_count'],'recent_high_pest_activity'=>(int)$pest['high_critical_count'] > 0],
        'irrigation'=>['system_count'=>(int)$water['system_count'],'recent_water_liters'=>(float)$water['recent_water_liters'],'recent_issue_count'=>(int)$water['recent_issue_count'],'recent_irrigation_issue'=>(int)$water['recent_issue_count'] > 0],
        'weather'=>['limited_weather_data'=>$latestWeather === null,'latest'=>$latestWeather,'recent_rainfall_recorded'=>$latestWeather !== null && $latestWeather['rainfall_mm'] !== null]
    ];
}

function yieldPredictionFind($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM yield_predictions WHERE id = ?');
    $stmt->execute([$id]);
    $prediction = $stmt->fetch();
    if (!$prediction) throw new FarmApiError(404, 'Prediction not found');
    $field = fieldFind($pdo, $prediction['field_id'], $user);
    $prediction['field_name'] = $field['name'];
    $prediction['factor_context'] = json_decode($prediction['factor_context_json'], true);
    return $prediction;
}

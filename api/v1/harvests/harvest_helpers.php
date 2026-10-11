<?php
require_once __DIR__ . '/../farms/farm_helpers.php';

function harvestFind($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM harvests WHERE id = ?');
    $stmt->execute([$id]);
    $harvest = $stmt->fetch();
    if (!$harvest) throw new FarmApiError(404, 'Harvest not found');
    fieldFind($pdo, $harvest['field_id'], $user);
    return $harvest;
}

function harvestQuantity($value, $name, $allowZero = false) {
    if ($value === null) return null;
    return farmNumber($value, $name, $allowZero ? 0 : 0.0001, 99999999999.9999);
}

function harvestValues($data, $create = false) {
    $values = [];
    if ($create) $values['crop_name_snapshot'] = farmText($data['crop_name_snapshot'] ?? null, 'crop_name_snapshot', 255, true);
    if ($create && array_key_exists('variety_snapshot', $data)) $values['variety_snapshot'] = farmText($data['variety_snapshot'], 'variety_snapshot', 255);
    foreach (['expected_harvest_date','actual_harvest_date'] as $key) {
        if (array_key_exists($key, $data)) $values[$key] = farmDate($data[$key], $key);
    }
    foreach (['expected_quantity_kg','actual_quantity_kg','area_harvested_hectares'] as $key) {
        if (array_key_exists($key, $data)) {
            $values[$key] = harvestQuantity($data[$key], $key, $key === 'actual_quantity_kg');
            if ($key === 'area_harvested_hectares' && $data[$key] !== null && $values[$key] === null) throw new FarmApiError(400, 'Invalid harvested area');
        }
    }
    if (array_key_exists('loss_quantity_kg', $data)) {
        $values['loss_quantity_kg'] = harvestQuantity($data['loss_quantity_kg'], 'loss_quantity_kg', true);
        if ($values['loss_quantity_kg'] === null) throw new FarmApiError(400, 'Invalid loss_quantity_kg');
    }
    foreach (['grade'=>100,'quality_notes'=>65535,'notes'=>65535] as $key=>$max) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, $max);
    }
    if (array_key_exists('status', $data)) {
        if (!is_string($data['status']) || !in_array($data['status'], ['planned','in_progress','completed','cancelled'], true)) throw new FarmApiError(400, 'Invalid status');
        $values['status'] = $data['status'];
    }
    return $values;
}

function harvestValidateFinal($record, $field) {
    $actual = $record['actual_quantity_kg'] ?? null;
    $loss = $record['loss_quantity_kg'] ?? 0;
    $area = $record['area_harvested_hectares'] ?? null;
    if ((float)$loss > 0 && $actual === null) throw new FarmApiError(400, 'Actual quantity is required before recording loss');
    if ($actual !== null && (float)$loss > (float)$actual) throw new FarmApiError(400, 'Loss cannot exceed actual quantity');
    if ($area !== null && (float)$area > (float)$field['area_hectares']) throw new FarmApiError(400, 'Harvested area exceeds field area');
    if (($record['status'] ?? 'planned') === 'completed' &&
        (($record['actual_harvest_date'] ?? null) === null || $actual === null || $area === null)) {
        throw new FarmApiError(400, 'Completed harvest requires actual date, quantity and harvested area');
    }
}

function harvestAnalysis($record) {
    $expected = $record['expected_quantity_kg'] === null ? null : (float)$record['expected_quantity_kg'];
    $actual = $record['actual_quantity_kg'] === null ? null : (float)$record['actual_quantity_kg'];
    $loss = (float)$record['loss_quantity_kg'];
    $area = $record['area_harvested_hectares'] === null ? null : (float)$record['area_harvested_hectares'];
    $difference = $expected !== null && $actual !== null ? $actual - $expected : null;
    $dateVariance = null;
    if ($record['expected_harvest_date'] !== null && $record['actual_harvest_date'] !== null) {
        $dateVariance = (int)(new DateTimeImmutable($record['expected_harvest_date']))->diff(new DateTimeImmutable($record['actual_harvest_date']))->format('%r%a');
    }
    return [
        'expected_quantity_kg'=>$expected,
        'actual_quantity_kg'=>$actual,
        'difference_kg'=>$difference === null ? null : round($difference, 4),
        'difference_percent'=>$difference === null || $expected <= 0 ? null : round($difference / $expected * 100, 2),
        'expected_harvest_date'=>$record['expected_harvest_date'],
        'actual_harvest_date'=>$record['actual_harvest_date'],
        'date_variance_days'=>$dateVariance,
        'loss_quantity_kg'=>$loss,
        'marketable_quantity_kg'=>$actual === null ? null : round($actual - $loss, 4),
        'loss_percent'=>$actual === null || $actual <= 0 ? null : round($loss / $actual * 100, 2),
        'gross_yield_kg_per_hectare'=>$actual === null || $area === null || $area <= 0 ? null : round($actual / $area, 4),
        'marketable_yield_kg_per_hectare'=>$actual === null || $area === null || $area <= 0 ? null : round(($actual - $loss) / $area, 4)
    ];
}

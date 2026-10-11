<?php
require_once __DIR__ . '/../farms/farm_helpers.php';

function irrigationChoice($value, $name, $choices) {
    if (!is_string($value) || !in_array($value, $choices, true)) throw new FarmApiError(400, 'Invalid ' . $name);
    return $value;
}

function irrigationPositive($value, $name) {
    if ($value === null) throw new FarmApiError(400, 'Missing ' . $name);
    return farmNumber($value, $name, 0.01, 9999999999999.99);
}

function irrigationMinutes($value) {
    if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
        throw new FarmApiError(400, 'Invalid duration_minutes');
    }
    return (int)$value;
}

function irrigationBoolean($value) {
    if (!is_bool($value)) throw new FarmApiError(400, 'Invalid weather_adjusted');
    return $value ? 1 : 0;
}

function irrigationField($pdo, $fieldId, $farmId) {
    if ($fieldId === null) return null;
    $fieldId = farmId($fieldId, 'field_id');
    $stmt = $pdo->prepare('SELECT farm_id FROM fields WHERE id = ?');
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch();
    if (!$field) throw new FarmApiError(404, 'Field not found');
    if ((int)$field['farm_id'] !== (int)$farmId) throw new FarmApiError(409, 'Field belongs to another farm');
    return $fieldId;
}

function irrigationSystem($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM irrigation_systems WHERE id = ?');
    $stmt->execute([$id]);
    $system = $stmt->fetch();
    if (!$system) throw new FarmApiError(404, 'Irrigation system not found');
    farmFind($pdo, $system['farm_id'], $user);
    return $system;
}

function irrigationSchedule($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM irrigation_schedules WHERE id = ?');
    $stmt->execute([$id]);
    $schedule = $stmt->fetch();
    if (!$schedule) throw new FarmApiError(404, 'Irrigation schedule not found');
    irrigationSystem($pdo, $schedule['irrigation_system_id'], $user);
    return $schedule;
}

function irrigationSystemValues($pdo, $data, $farmId, $create = false) {
    $values = [];
    if ($create || array_key_exists('name', $data)) $values['name'] = farmText($data['name'] ?? null, 'name', 255, true);
    if ($create || array_key_exists('system_type', $data)) {
        $values['system_type'] = irrigationChoice($data['system_type'] ?? null, 'system_type', ['drip','sprinkler','flood','manual','other']);
    }
    if (array_key_exists('field_id', $data)) $values['field_id'] = irrigationField($pdo, $data['field_id'], $farmId);
    foreach (['water_source_name'=>255,'pump_name'=>255,'notes'=>65535] as $key=>$max) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, $max);
    }
    if (array_key_exists('water_source_type', $data)) {
        $values['water_source_type'] = $data['water_source_type'] === null ? null : irrigationChoice($data['water_source_type'], 'water_source_type', ['borehole','well','river','reservoir','tank','municipal','other']);
    }
    foreach (['water_source_capacity_liters','flow_rate_lph'] as $key) {
        if (array_key_exists($key, $data)) $values[$key] = $data[$key] === null ? null : irrigationPositive($data[$key], $key);
    }
    if (array_key_exists('pump_status', $data)) {
        $values['pump_status'] = $data['pump_status'] === null ? null : irrigationChoice($data['pump_status'], 'pump_status', ['operational','maintenance','fault','offline']);
    }
    if (array_key_exists('installation_date', $data)) $values['installation_date'] = farmDate($data['installation_date'], 'installation_date');
    if (!$create && array_key_exists('status', $data)) $values['status'] = irrigationChoice($data['status'], 'status', ['active','inactive','maintenance']);
    return $values;
}

function irrigationScheduleValues($data, $create = false) {
    $values = [];
    if ($create || array_key_exists('scheduled_at', $data)) {
        $values['scheduled_at'] = farmDate($data['scheduled_at'] ?? null, 'scheduled_at', 'Y-m-d H:i:s');
        if ($values['scheduled_at'] === null) throw new FarmApiError(400, 'Missing scheduled_at');
    }
    if ($create || array_key_exists('duration_minutes', $data)) $values['duration_minutes'] = irrigationMinutes($data['duration_minutes'] ?? null);
    if (array_key_exists('planned_volume_liters', $data)) {
        $values['planned_volume_liters'] = $data['planned_volume_liters'] === null ? null : irrigationPositive($data['planned_volume_liters'], 'planned_volume_liters');
    }
    if (array_key_exists('status', $data)) $values['status'] = irrigationChoice($data['status'], 'status', ['scheduled','completed','skipped','cancelled']);
    if (array_key_exists('weather_adjusted', $data)) $values['weather_adjusted'] = irrigationBoolean($data['weather_adjusted']);
    if (array_key_exists('notes', $data)) $values['notes'] = farmText($data['notes'], 'notes', 65535);
    return $values;
}

function irrigationUsageValues($data) {
    $values = [];
    $values['logged_at'] = farmDate($data['logged_at'] ?? null, 'logged_at', 'Y-m-d H:i:s');
    if ($values['logged_at'] === null) throw new FarmApiError(400, 'Missing logged_at');
    $values['volume_liters'] = irrigationPositive($data['volume_liters'] ?? null, 'volume_liters');
    $values['duration_minutes'] = irrigationMinutes($data['duration_minutes'] ?? null);
    foreach (['energy_kwh','cost_amount'] as $key) {
        if (array_key_exists($key, $data)) $values[$key] = farmNumber($data[$key], $key, 0, 9999999999999.99);
    }
    if (array_key_exists('pump_status', $data)) {
        $values['pump_status'] = $data['pump_status'] === null ? null : irrigationChoice($data['pump_status'], 'pump_status', ['operational','maintenance','fault','offline']);
    }
    if (array_key_exists('issue_notes', $data)) $values['issue_notes'] = farmText($data['issue_notes'], 'issue_notes', 65535);
    return $values;
}

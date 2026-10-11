<?php
require_once __DIR__ . '/../farms/farm_helpers.php';

function pestAdmin($user) {
    if (($user['role'] ?? null) !== 'admin') throw new FarmApiError(403, 'Admin role required');
}

function pestChoice($value, $name, $choices) {
    if (!is_string($value) || !in_array($value, $choices, true)) throw new FarmApiError(400, 'Invalid ' . $name);
    return $value;
}

function pestCatalog($pdo, $id, $user, $activeOnly = false) {
    $stmt = $pdo->prepare('SELECT * FROM pest_disease_catalog WHERE id = ?');
    $stmt->execute([$id]);
    $entry = $stmt->fetch();
    if (!$entry) throw new FarmApiError(404, 'Catalogue entry not found');
    if ($entry['status'] !== 'active' && ($activeOnly || ($user['role'] ?? null) !== 'admin')) {
        throw new FarmApiError(404, 'Catalogue entry not found');
    }
    return $entry;
}

function pestCatalogValues($data, $create = false) {
    $values = [];
    if ($create || array_key_exists('issue_type', $data)) $values['issue_type'] = pestChoice($data['issue_type'] ?? null, 'issue_type', ['pest','disease']);
    if ($create || array_key_exists('common_name', $data)) $values['common_name'] = farmText($data['common_name'] ?? null, 'common_name', 255, true);
    foreach (['scientific_name'=>255,'description'=>65535,'common_symptoms'=>65535,'recommended_control'=>65535,'prevention_notes'=>65535] as $key=>$max) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, $max);
    }
    if (array_key_exists('default_severity', $data)) {
        $values['default_severity'] = $data['default_severity'] === null ? null : pestChoice($data['default_severity'], 'default_severity', ['low','moderate','high','critical']);
    }
    return $values;
}

function pestCatalogUnique($pdo, $type, $name, $exceptId = null) {
    $stmt = $pdo->prepare('SELECT id FROM pest_disease_catalog WHERE issue_type = ? AND common_name = ? AND (? IS NULL OR id <> ?)');
    $stmt->execute([$type, $name, $exceptId, $exceptId]);
    if ($stmt->fetch()) throw new FarmApiError(409, 'Catalogue entry already exists');
}

function pestScout($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM scouting_reports WHERE id = ?');
    $stmt->execute([$id]);
    $report = $stmt->fetch();
    if (!$report) throw new FarmApiError(404, 'Scouting report not found');
    fieldFind($pdo, $report['field_id'], $user);
    return $report;
}

function pestTreatment($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM treatments WHERE id = ?');
    $stmt->execute([$id]);
    $treatment = $stmt->fetch();
    if (!$treatment) throw new FarmApiError(404, 'Treatment not found');
    pestScout($pdo, $treatment['scouting_report_id'], $user);
    return $treatment;
}

function pestArea($value, $field) {
    if ($value === null) return null;
    $area = farmNumber($value, 'affected_area_hectares', 0.0001, 99999999999.9999);
    if ($area > (float)$field['area_hectares']) throw new FarmApiError(400, 'Affected area exceeds field area');
    return $area;
}

function pestScoutValues($pdo, $data, $user, $field, $create = false) {
    $values = [];
    if ($create || array_key_exists('observed_at', $data)) {
        $values['observed_at'] = farmDate($data['observed_at'] ?? null, 'observed_at', 'Y-m-d H:i:s');
        if ($values['observed_at'] === null) throw new FarmApiError(400, 'Missing observed_at');
    }
    if ($create || array_key_exists('severity', $data)) $values['severity'] = pestChoice($data['severity'] ?? null, 'severity', ['low','moderate','high','critical']);
    if ($create) {
        $values['catalog_id'] = null;
        if (array_key_exists('catalog_id', $data) && $data['catalog_id'] !== null) {
            $values['catalog_id'] = farmId($data['catalog_id'], 'catalog_id');
            pestCatalog($pdo, $values['catalog_id'], $user, true);
        }
        $values['observed_issue_name'] = farmText($data['observed_issue_name'] ?? null, 'observed_issue_name', 255);
        if ($values['catalog_id'] === null && $values['observed_issue_name'] === null) throw new FarmApiError(400, 'Supply catalog_id or observed_issue_name');
    }
    if (array_key_exists('affected_area_hectares', $data)) $values['affected_area_hectares'] = pestArea($data['affected_area_hectares'], $field);
    if (array_key_exists('incidence_percent', $data)) $values['incidence_percent'] = farmNumber($data['incidence_percent'], 'incidence_percent', 0, 100);
    foreach (['symptoms_notes','recommended_action_notes'] as $key) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, 65535);
    }
    if (array_key_exists('status', $data)) $values['status'] = pestChoice($data['status'], 'status', ['open','monitoring','treated','resolved']);
    return $values;
}

function pestPositive($value, $name) {
    if ($value === null) return null;
    return farmNumber($value, $name, 0.0001, 99999999999.9999);
}

function pestTreatmentValues($data, $create = false, $existing = []) {
    $values = [];
    if ($create) {
        $values['treatment_type'] = pestChoice($data['treatment_type'] ?? null, 'treatment_type', ['chemical','biological','mechanical','cultural','other']);
        $values['product_or_method'] = farmText($data['product_or_method'] ?? null, 'product_or_method', 255, true);
    }
    foreach (['active_ingredient'=>255,'application_rate_unit'=>100,'quantity_unit'=>100,'effectiveness_notes'=>65535] as $key=>$max) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, $max);
    }
    foreach (['application_rate','quantity_used'] as $key) {
        if (array_key_exists($key, $data)) $values[$key] = pestPositive($data[$key], $key);
    }
    foreach (['application_rate'=>'application_rate_unit','quantity_used'=>'quantity_unit'] as $amount=>$unit) {
        $currentAmount = array_key_exists($amount, $values) ? $values[$amount] : ($existing[$amount] ?? null);
        $currentUnit = array_key_exists($unit, $values) ? $values[$unit] : ($existing[$unit] ?? null);
        if ($currentAmount !== null && $currentUnit === null) throw new FarmApiError(400, 'Missing ' . $unit);
    }
    if (array_key_exists('applied_at', $data)) $values['applied_at'] = farmDate($data['applied_at'], 'applied_at', 'Y-m-d H:i:s');
    if (array_key_exists('status', $data)) $values['status'] = pestChoice($data['status'], 'status', ['planned','applied','monitoring','completed']);
    if (array_key_exists('effectiveness_percent', $data)) $values['effectiveness_percent'] = farmNumber($data['effectiveness_percent'], 'effectiveness_percent', 0, 100);
    if (array_key_exists('follow_up_date', $data)) $values['follow_up_date'] = farmDate($data['follow_up_date'], 'follow_up_date');
    return $values;
}

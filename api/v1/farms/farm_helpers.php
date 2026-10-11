<?php
// Developer 1 helpers for direct PHP endpoints. Authentication stays in auth.php.
class FarmApiError extends Exception {
    public $status;
    public function __construct($status, $message) {
        parent::__construct($message);
        $this->status = $status;
    }
}

function farmRespond($status, $data = null, $error = null) {
    http_response_code($status);
    echo json_encode(['success' => $error === null, 'data' => $data, 'error' => $error]);
    exit;
}

function farmRun($method, $callback) {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../../db.php';
    require_once __DIR__ . '/../../../auth.php';
    $user = verifyJWT();
    if ($_SERVER['REQUEST_METHOD'] !== $method) farmRespond(405, null, 'Method not allowed');
    try {
        $pdo = getDbConnection();
        $callback($pdo, $user);
    } catch (FarmApiError $e) {
        farmRespond($e->status, null, $e->getMessage());
    } catch (Throwable $e) {
        farmRespond(500, null, 'Request failed');
    }
}

function farmJson() {
    $raw = file_get_contents('php://input');
    $object = json_decode($raw);
    if (!is_object($object) || json_last_error() !== JSON_ERROR_NONE) {
        throw new FarmApiError(400, 'Expected a JSON object');
    }
    return json_decode($raw, true);
}

function farmId($value, $name = 'id') {
    if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        throw new FarmApiError(400, 'Invalid ' . $name);
    }
    return (int)$value;
}

function farmOwned($farm, $user) {
    return ($user['role'] ?? null) === 'admin' || (int)$farm['owner_user_id'] === (int)$user['id'];
}

function farmFind($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT * FROM farms WHERE id = ?');
    $stmt->execute([$id]);
    $farm = $stmt->fetch();
    if (!$farm) throw new FarmApiError(404, 'Farm not found');
    if (!farmOwned($farm, $user)) throw new FarmApiError(403, 'Forbidden');
    return $farm;
}

function fieldFind($pdo, $id, $user) {
    $stmt = $pdo->prepare('SELECT fields.* FROM fields WHERE fields.id = ?');
    $stmt->execute([$id]);
    $field = $stmt->fetch();
    if (!$field) throw new FarmApiError(404, 'Field not found');
    farmFind($pdo, $field['farm_id'], $user);
    return $field;
}

function farmText($value, $name, $max, $required = false) {
    if ($value === null && !$required) return null;
    if (!is_string($value)) throw new FarmApiError(400, 'Invalid ' . $name);
    $value = trim($value);
    if ($required && $value === '') throw new FarmApiError(400, 'Missing ' . $name);
    if (strlen($value) > $max) throw new FarmApiError(400, 'Invalid ' . $name);
    return $value === '' ? null : $value;
}

function farmNumber($value, $name, $min, $max = null, $positive = false) {
    if ($value === null) return null;
    if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
        throw new FarmApiError(400, 'Invalid ' . $name);
    }
    $number = (float)$value;
    if (!is_finite($number) || $number < $min || ($positive && $number <= 0) || ($max !== null && $number > $max)) {
        throw new FarmApiError(400, 'Invalid ' . $name);
    }
    return $number;
}

function farmCoordinates($data, &$values) {
    $hasLat = array_key_exists('latitude', $data);
    $hasLon = array_key_exists('longitude', $data);
    if ($hasLat !== $hasLon) throw new FarmApiError(400, 'Latitude and longitude must be supplied together');
    if ($hasLat) {
        if (($data['latitude'] === null) !== ($data['longitude'] === null)) {
            throw new FarmApiError(400, 'Latitude and longitude must be supplied together');
        }
        $values['latitude'] = farmNumber($data['latitude'], 'latitude', -90, 90);
        $values['longitude'] = farmNumber($data['longitude'], 'longitude', -180, 180);
    }
}

function farmDate($value, $name, $format = 'Y-m-d') {
    if ($value === null) return null;
    if (!is_string($value)) throw new FarmApiError(400, 'Invalid ' . $name);
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
    if (!$date || $date->format($format) !== $value) throw new FarmApiError(400, 'Invalid ' . $name);
    return $value;
}

function farmBoundary($value) {
    if ($value === null) return null;
    $geometry = is_string($value) ? json_decode($value, true) : $value;
    if ((is_string($value) && json_last_error() !== JSON_ERROR_NONE) || !is_array($geometry) ||
        !in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true) ||
        !isset($geometry['coordinates']) || !is_array($geometry['coordinates']) || !$geometry['coordinates']) {
        throw new FarmApiError(400, 'Invalid boundary_geojson');
    }
    $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
    foreach ($polygons as $polygon) {
        if (!is_array($polygon) || !$polygon) throw new FarmApiError(400, 'Invalid boundary_geojson');
        foreach ($polygon as $ring) {
            if (!is_array($ring) || count($ring) < 4) throw new FarmApiError(400, 'Invalid boundary_geojson');
            foreach ($ring as $point) {
                if (!is_array($point) || count($point) < 2 || !is_numeric($point[0]) || !is_numeric($point[1]) ||
                    $point[0] < -180 || $point[0] > 180 || $point[1] < -90 || $point[1] > 90) {
                    throw new FarmApiError(400, 'Invalid boundary_geojson');
                }
            }
            if ($ring[0] !== end($ring)) throw new FarmApiError(400, 'Boundary rings must be closed');
        }
    }
    return json_encode(['type' => $geometry['type'], 'coordinates' => $geometry['coordinates']], JSON_THROW_ON_ERROR);
}

function farmProfile($data, $create = false) {
    $values = [];
    if ($create || array_key_exists('name', $data)) $values['name'] = farmText($data['name'] ?? null, 'name', 255, true);
    foreach (['location_name' => 255, 'description' => 65535] as $key => $max) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, $max);
    }
    if (array_key_exists('total_area_hectares', $data)) {
        $values['total_area_hectares'] = farmNumber($data['total_area_hectares'], 'total_area_hectares', 0.0001, 99999999999.9999);
    }
    farmCoordinates($data, $values);
    return $values;
}

function fieldProfile($data, $create = false) {
    $values = [];
    if ($create || array_key_exists('name', $data)) $values['name'] = farmText($data['name'] ?? null, 'name', 255, true);
    if ($create || array_key_exists('area_hectares', $data)) {
        $values['area_hectares'] = farmNumber($data['area_hectares'] ?? null, 'area_hectares', 0.0001, 99999999999.9999);
        if ($values['area_hectares'] === null) throw new FarmApiError(400, 'Missing area_hectares');
    }
    foreach (['code' => 100, 'land_use' => 100, 'land_use_notes' => 65535] as $key => $max) {
        if (array_key_exists($key, $data)) $values[$key] = farmText($data[$key], $key, $max);
    }
    if (array_key_exists('boundary_geojson', $data)) $values['boundary_geojson'] = farmBoundary($data['boundary_geojson']);
    farmCoordinates($data, $values);
    return $values;
}

function farmUpdateColumns($pdo, $table, $id, $values) {
    if (!$values) throw new FarmApiError(400, 'No editable fields supplied');
    // Keys only come from the fixed validation maps in farmProfile/fieldProfile.
    $sets = implode(', ', array_map(function ($key) { return $key . ' = ?'; }, array_keys($values)));
    $stmt = $pdo->prepare('UPDATE ' . $table . ' SET ' . $sets . ' WHERE id = ?');
    $stmt->execute(array_merge(array_values($values), [$id]));
}

function farmFieldCodeAvailable($pdo, $farmId, $code, $exceptId = null) {
    if ($code === null) return;
    $stmt = $pdo->prepare('SELECT id FROM fields WHERE farm_id = ? AND code = ? AND (? IS NULL OR id <> ?)');
    $stmt->execute([$farmId, $code, $exceptId, $exceptId]);
    if ($stmt->fetch()) throw new FarmApiError(409, 'Field code already exists in this farm');
}

<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "123456";
$db   = "pawconnect";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// ── Module router ─────────────────────────────────────────────────
$module = trim($_GET['module'] ?? '');
if (!in_array($module, ['pets', 'products'])) {
    echo json_encode(['success' => false,
                      'message' => "Invalid module. Use: pets or products."]);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// ── Shared helper: frequency score ───────────────────────────────
// Returns 0.0–1.0 based on how often $value appears in $history
function frequencyScore(array $history, string $value): float {
    if (empty($history)) return 0.0;
    $count = array_count_values($history);
    $max   = max($count);
    return isset($count[$value]) ? ($count[$value] / $max) : 0.0;
}

//  MODULE: PETS
if ($module === 'pets') {

    $petId = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;

    // 1. Log this view
    if ($userId > 0 && $petId > 0) {
        $pdo->prepare("INSERT INTO user_activity
                       (user_id, item_type, item_id, action)
                       VALUES (?, 'pet', ?, 'view')")
            ->execute([$userId, $petId]);
    }

    // 2. Build profile from history
    $profile = ['types' => [], 'breeds' => [], 'terms' => []];

    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT p.type, p.breed
                               FROM user_activity ua
                               JOIN pet p ON p.id = ua.item_id
                               WHERE ua.user_id = ?
                                 AND ua.item_type = 'pet'
                                 AND ua.action IN ('view','click')
                               ORDER BY ua.created_at DESC
                               LIMIT 20");
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $profile['types'][]  = strtolower($row['type']);
            $profile['breeds'][] = strtolower($row['breed']);
        }

        $stmt = $pdo->prepare("SELECT search_term FROM user_activity
                               WHERE user_id = ? AND item_type = 'pet'
                                 AND action = 'search' AND search_term IS NOT NULL
                               ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $profile['terms'][] = strtolower($row['search_term']);
        }
    }

    // 3. Get current pet
    $currentPet = null;
    if ($petId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM pet WHERE id = ? LIMIT 1");
        $stmt->execute([$petId]);
        $currentPet = $stmt->fetch();
    }

    // 4. Fetch all other available pets
    $stmt = $pdo->prepare("SELECT * FROM pet WHERE id != ? AND status = 'available'");
    $stmt->execute([$petId ?: 0]);
    $allPets = $stmt->fetchAll();

    // 5. Score each pet
    //
    // SCORING (max ~10 pts):
    //   A. Current-item similarity:
    //      +3.0  same type (dog/cat)
    //      +2.0  same breed
    //      +1.0  same urgency
    //      +1.0  same location
    //   B. User history:
    //      +2.0  type matches most-viewed type  (frequency-weighted)
    //      +1.5  breed matches most-viewed breed (frequency-weighted)
    //      +1.0  name/breed matches a past search term (similar_text)

    foreach ($allPets as &$pet) {
        $score = 0.0;

        // A. Current-item similarity
        if ($currentPet) {
            if (strtolower($pet['type'])     === strtolower($currentPet['type']))     $score += 3.0;
            if (strtolower($pet['breed'])    === strtolower($currentPet['breed']))    $score += 2.0;
            if (strtolower($pet['urgency'])  === strtolower($currentPet['urgency']))  $score += 1.0;
            if (strtolower($pet['location'] ?? '') === strtolower($currentPet['location'] ?? '')) $score += 1.0;
        }

        // B. User history
        if (!empty($profile['types']))
            $score += frequencyScore($profile['types'],  strtolower($pet['type']))  * 2.0;
        if (!empty($profile['breeds']))
            $score += frequencyScore($profile['breeds'], strtolower($pet['breed'])) * 1.5;

        foreach ($profile['terms'] as $term) {
            similar_text($term, strtolower($pet['name']),  $p1);
            similar_text($term, strtolower($pet['breed']), $p2);
            $score += (max($p1, $p2) / 100) * 1.0;
        }

        $pet['score'] = round($score, 4);
    }
    unset($pet);

    // 6. Sort, filter, return top 6
    usort($allPets, fn($a, $b) => $b['score'] <=> $a['score']);
    $recommendations = array_slice(
        array_values(array_filter($allPets, fn($p) => $p['score'] > 0)),
        0, 6
    );

    echo json_encode(['success' => true, 'module' => 'pets',
                      'recommendations' => $recommendations]);
}

//  MODULE: PRODUCTS
elseif ($module === 'products') {

    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

    // 1. Log this view
    if ($userId > 0 && $productId > 0) {
        $pdo->prepare("INSERT INTO user_activity
                       (user_id, item_type, item_id, action)
                       VALUES (?, 'product', ?, 'view')")
            ->execute([$userId, $productId]);
    }

    // 2. Build profile from history
    $profile = ['categories' => [], 'terms' => []];

    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT p.category
                               FROM user_activity ua
                               JOIN product p ON p.id = ua.item_id
                               WHERE ua.user_id = ?
                                 AND ua.item_type = 'product'
                                 AND ua.action IN ('view','click')
                               ORDER BY ua.created_at DESC
                               LIMIT 20");
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $profile['categories'][] = strtolower($row['category']);
        }

        $stmt = $pdo->prepare("SELECT search_term FROM user_activity
                               WHERE user_id = ? AND item_type = 'product'
                                 AND action = 'search' AND search_term IS NOT NULL
                               ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $profile['terms'][] = strtolower($row['search_term']);
        }
    }

    // 3. Get current product
    $currentProduct = null;
    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $currentProduct = $stmt->fetch();
    }

    // 4. Fetch all other products
    $stmt = $pdo->prepare("SELECT * FROM product WHERE id != ?");
    $stmt->execute([$productId ?: 0]);
    $allProducts = $stmt->fetchAll();

    // 5. Score each product
    //
    // SCORING (max ~8 pts):
    //   A. Current-item similarity:
    //      +3.0  same category
    //      +1.5  name similarity     (similar_text)
    //      +1.0  description keyword overlap (capped)
    //   B. User history:
    //      +2.0  category matches most-viewed category (frequency-weighted)
    //      +1.0  name/category/description matches a past search term

    foreach ($allProducts as &$product) {
        $score = 0.0;

        // A. Current-item similarity
        if ($currentProduct) {
            if (strtolower($product['category']) === strtolower($currentProduct['category']))
                $score += 3.0;

            similar_text(strtolower($currentProduct['name']),
                         strtolower($product['name']), $namePct);
            $score += ($namePct / 100) * 1.5;

            $stop   = ['the','a','an','is','in','of','for','and','to','with','that','it','this'];
            $wordsA = array_diff(array_filter(explode(' ', strtolower($currentProduct['description'] ?? ''))), $stop);
            $wordsB = array_diff(array_filter(explode(' ', strtolower($product['description'] ?? ''))), $stop);
            $score += min(count(array_intersect($wordsA, $wordsB)) * 0.1, 1.0);
        }

        // B. User history
        if (!empty($profile['categories']))
            $score += frequencyScore($profile['categories'],
                                     strtolower($product['category'])) * 2.0;

        foreach ($profile['terms'] as $term) {
            similar_text($term, strtolower($product['name']),        $p1);
            similar_text($term, strtolower($product['category']),    $p2);
            similar_text($term, strtolower($product['description']), $p3);
            $score += (max($p1, $p2, $p3) / 100) * 1.0;
        }

        $product['score'] = round($score, 4);
    }
    unset($product);

    // 6. Sort, filter, return top 6
    usort($allProducts, fn($a, $b) => $b['score'] <=> $a['score']);
    $recommendations = array_slice(
        array_values(array_filter($allProducts, fn($p) => $p['score'] > 0)),
        0, 6
    );

    echo json_encode(['success' => true, 'module' => 'products',
                      'recommendations' => $recommendations]);
}
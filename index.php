<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';
require_once 'classes/Product.php';
require_once 'classes/Ticket.php';

$db = new Database();
$conn = $db->getConnection();

// Son eklenen aktif çekilişleri al
$query = "SELECT p.*, u.name as seller_name, 
          (SELECT COUNT(*) FROM tickets t WHERE t.product_id = p.id) as sold_tickets
          FROM products p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE p.status = 'active'
          ORDER BY p.created_at DESC 
          LIMIT 6";
$stmt = $conn->query($query);
$latest_raffles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Son kazananları products tablosundan al
$query = "SELECT p.title, u.name as winner_name, p.draw_date
          FROM products p
          JOIN users u ON p.winner_user_id = u.id
          WHERE p.status = 'completed' 
          AND p.winner_user_id IS NOT NULL
          ORDER BY p.draw_date DESC
          LIMIT 5";
$stmt = $conn->query($query);
$recent_winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'templates/header.php';
?>

<!-- Üst Banner -->
<div class="container-fluid bg-light py-4 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="stats-box text-center me-4">
                        <h3 class="mb-0 text-primary"><?php echo count($latest_raffles); ?></h3>
                        <small class="text-muted">Aktiv Çəkiliş</small>
                    </div>
                    <div class="stats-box text-center me-4">
                        <h3 class="mb-0 text-success"><?php echo count($recent_winners); ?></h3>
                        <small class="text-muted">Son Qaliblər</small>
                    </div>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="pages/register.php" class="btn btn-primary ms-auto">
                            <i class="fas fa-user-plus me-2"></i>İndi Qeydiyyatdan Keç
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="pages/active-raffles.php" class="btn btn-outline-primary">
                    <i class="fas fa-ticket-alt me-2"></i>Bütün Çəkilişlər
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Son Əlavə Edilən Çəkilişlər -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-star text-warning me-2"></i>
            Son Əlavə Edilən Çəkilişlər
        </h2>
        <a href="pages/active-raffles.php" class="btn btn-sm btn-link text-decoration-none">
            Hamısını Gör <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    
    <?php if(empty($latest_raffles)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Hələ aktiv çəkiliş yoxdur.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($latest_raffles as $raffle): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm hover-card">
                        <?php 
                        $images = explode(',', $raffle['images']);
                        $first_image = !empty($images[0]) ? $images[0] : 'default.jpg';
                        ?>
                        <div class="position-relative">
                            <img src="assets/images/uploads/<?php echo $first_image; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($raffle['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-primary">
                                ₺<?php echo number_format($raffle['ticket_price'], 2); ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title text-truncate">
                                <?php echo htmlspecialchars($raffle['title']); ?>
                            </h5>
                            
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-user-circle text-muted me-2"></i>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($raffle['seller_name']); ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Doluluk</small>
                                    <small class="text-primary">
                                        <?php echo $raffle['sold_tickets']; ?>/<?php echo $raffle['ticket_count']; ?>
                                    </small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo ($raffle['sold_tickets'] / $raffle['ticket_count']) * 100; ?>%">
                                    </div>
                                </div>
                            </div>
                            
                            <a href="pages/product-detail.php?id=<?php echo $raffle['id']; ?>" 
                               class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-ticket-alt me-1"></i>
                                İndi Qatıl
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Son Qaliblər -->
<div class="container-fluid bg-light py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-trophy text-warning me-2"></i>
                Son Qaliblər
            </h2>
            <a href="pages/raffle-results.php" class="btn btn-sm btn-link text-decoration-none">
                Hamısını Gör <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <?php if(empty($recent_winners)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Hələ nəticələnən çəkiliş yoxdur.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($recent_winners as $winner): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="winner-icon bg-warning text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($winner['title']); ?></h6>
                                        <p class="mb-0 text-success">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($winner['winner_name']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($winner['draw_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-card {
    transition: transform 0.2s ease-in-out;
}
.hover-card:hover {
    transform: translateY(-5px);
}
.winner-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stats-box {
    padding: 10px 20px;
    border-right: 1px solid #dee2e6;
}
.stats-box:last-child {
    border-right: none;
}
</style>

<?php include 'templates/footer.php'; ?> 
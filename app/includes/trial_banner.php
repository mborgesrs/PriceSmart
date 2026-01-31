<?php
$days_left = $_SESSION['trial_days_left'] ?? null;
$expired = $_SESSION['trial_expired'] ?? false;
$plan = $_SESSION['user_plan'] ?? 'Free';

if ($plan !== 'Free' && ($days_left !== null || $expired)): 
    $banner_class = $expired ? 'banner-danger' : ($days_left <= 3 ? 'banner-warning' : 'banner-info');
    $icon = $expired ? 'alert-octagon' : 'clock';
    $message = $expired 
        ? "Seu período de teste do plano $plan expirou. Faça o upgrade para continuar usando os recursos Premium." 
        : "Você está no período de teste do plano $plan. Restam <strong>$days_left dia(s)</strong>.";
?>
<style>
    .trial-banner {
        padding: 0.75rem 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        font-size: 0.85rem;
        z-index: 5000;
        position: sticky;
        top: -2rem; /* Offsets main-content padding-top if needed, but safer to let it flow */
        margin: -2rem -3rem 2rem -3rem; /* Negative margins to flush with main-content edges */
        text-align: center;
    }
    .banner-info { background: #eff6ff; color: #1e40af; border-bottom: 1px solid #dbeafe; }
    .banner-warning { background: #fffbeb; color: #92400e; border-bottom: 1px solid #fef3c7; }
    .banner-danger { background: #fef2f2; color: #991b1b; border-bottom: 1px solid #fee2e2; }
    
    .trial-banner .btn-upgrade {
        padding: 0.35rem 1rem;
        background: currentColor;
        color: white; /* Will be inverted by background */
        border-radius: 6px;
        text-decoration: none;
        font-weight: 700;
        margin-left: 1rem;
        font-size: 0.75rem;
        transition: opacity 0.2s;
    }
    .banner-info .btn-upgrade { background: #3b82f6; }
    .banner-warning .btn-upgrade { background: #d97706; }
    .banner-danger .btn-upgrade { background: #ef4444; }
    
    .trial-banner .btn-upgrade:hover { opacity: 0.9; }
</style>

<div class="trial-banner <?= $banner_class ?>">
    <i data-lucide="<?= $icon ?>" style="width: 16px; height: 16px;"></i>
    <span><?= $message ?></span>
    <a href="settings.php" class="btn-upgrade">Ver Planos</a>
</div>

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
<?php endif; ?>

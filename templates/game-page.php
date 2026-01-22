<?php
if (!defined('ABSPATH')) {
    exit;
}

use CardFlipGame\Game;
use CardFlipGame\Database;

$user_id = get_current_user_id();
$balance = Database::get_user_balance($user_id);
$round_data = Game::get_current_round_data();
$cards = Game::get_cards();
$recent_winners = Database::get_recent_winners(3);
?>

<div class="cfg-game-container">
    <!-- Wallet Display -->
    <div class="cfg-wallet-bar">
        <span class="cfg-wallet-label">Wallet Balance:</span>
        <span class="cfg-wallet-amount" id="cfg-user-balance">₹<?php echo number_format($balance, 2); ?></span>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('wallet')); ?>" class="cfg-wallet-link">Manage Wallet</a>
    </div>

    <!-- Timer Display -->
    <div class="cfg-timer-section">
        <div class="cfg-timer-display" id="cfg-timer">
            <span id="cfg-timer-text">Loading...</span>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="cfg-cards-grid" id="cfg-cards-grid">
        <?php foreach ($cards as $card): ?>
            <?php $images = Game::get_card_images($card); ?>
            <div class="cfg-card" data-card="<?php echo esc_attr($card); ?>">
                <div class="cfg-card-inner">
                    <div class="cfg-card-front">
                        <img src="<?php echo esc_url($images['normal'] ?: CFG_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" alt="<?php echo esc_attr($card); ?>" class="cfg-card-image">
                        <h3 class="cfg-card-name"><?php echo esc_html($card); ?></h3>
                        <button class="cfg-book-btn" data-card="<?php echo esc_attr($card); ?>">BOOK</button>
                    </div>
                    <div class="cfg-card-back">
                        <img src="<?php echo esc_url($images['winner'] ?: CFG_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" alt="<?php echo esc_attr($card); ?> Winner" class="cfg-card-image cfg-winner-image">
                        <img src="<?php echo esc_url($images['loss'] ?: CFG_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" alt="<?php echo esc_attr($card); ?> Loss" class="cfg-card-image cfg-loss-image" style="display:none;">
                        <h3 class="cfg-result-text"></h3>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Previous Winners -->
    <div class="cfg-previous-winners">
        <h3>Previous Winners</h3>
        <div class="cfg-winners-list" id="cfg-winners-list">
            <?php if (!empty($recent_winners)): ?>
                <?php foreach ($recent_winners as $winner): ?>
                    <div class="cfg-winner-item">
                        <span class="cfg-winner-card"><?php echo esc_html($winner->winner_card); ?></span>
                        <span class="cfg-winner-time"><?php echo esc_html(date('M d, H:i', strtotime($winner->end_time))); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No previous winners yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div id="cfg-booking-modal" class="cfg-modal">
    <div class="cfg-modal-content">
        <span class="cfg-close">&times;</span>
        <div class="cfg-modal-body">
            <img id="cfg-modal-card-image" src="" alt="" class="cfg-modal-image">
            <h2 id="cfg-modal-card-name"></h2>
            <div class="cfg-quantity-selector">
                <button class="cfg-qty-btn" id="cfg-qty-minus">-</button>
                <input type="number" id="cfg-quantity" value="1" min="1" readonly>
                <button class="cfg-qty-btn" id="cfg-qty-plus">+</button>
            </div>
            <div class="cfg-cost-display">
                <span>Total Cost: ₹<span id="cfg-total-cost">10</span></span>
            </div>
            <button class="cfg-confirm-btn" id="cfg-confirm-booking">Confirm Booking</button>
        </div>
    </div>
</div>

<script>
var cfgCurrentCard = '';
var cfgCardCost = <?php echo get_option('cfg_card_cost', 10); ?>;
</script>

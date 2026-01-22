<?php
if (!defined('ABSPATH')) {
    exit;
}

use CardFlipGame\Database;

$user_id = get_current_user_id();
$balance = Database::get_user_balance($user_id);
$transactions = Database::get_wallet_transactions($user_id, 20);
$bookings = Database::get_user_bookings($user_id, 20);
$withdrawals = Database::get_withdrawal_requests($user_id);
?>

<div class="cfg-dashboard">
    <h1>My Dashboard</h1>

    <!-- Wallet Section -->
    <div class="cfg-dashboard-section">
        <h2>Wallet Balance</h2>
        <div class="cfg-balance-display">
            <span class="cfg-balance-amount">₹<?php echo number_format($balance, 2); ?></span>
        </div>
    </div>

    <!-- Withdrawal Section -->
    <div class="cfg-dashboard-section">
        <h2>Request Withdrawal</h2>
        <form id="cfg-withdrawal-form" class="cfg-form">
            <div class="cfg-form-group">
                <label for="withdrawal-amount">Amount (Minimum ₹<?php echo get_option('cfg_min_withdrawal', 200); ?>)</label>
                <input type="number" id="withdrawal-amount" name="amount" min="<?php echo get_option('cfg_min_withdrawal', 200); ?>" step="1" required>
            </div>
            <div class="cfg-form-group">
                <label for="upi-id">UPI ID</label>
                <input type="text" id="upi-id" name="upi_id" placeholder="yourname@upi" required>
            </div>
            <button type="submit" class="cfg-btn cfg-btn-primary">Submit Request</button>
        </form>
    </div>

    <!-- Withdrawal Requests -->
    <div class="cfg-dashboard-section">
        <h2>Withdrawal Requests</h2>
        <div class="cfg-table-responsive">
            <table class="cfg-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>UPI ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($withdrawals)): ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr>
                                <td><?php echo esc_html(date('M d, Y H:i', strtotime($withdrawal->request_date))); ?></td>
                                <td>₹<?php echo number_format($withdrawal->amount, 2); ?></td>
                                <td><?php echo esc_html($withdrawal->upi_id); ?></td>
                                <td><span class="cfg-status cfg-status-<?php echo esc_attr($withdrawal->status); ?>"><?php echo esc_html(ucfirst($withdrawal->status)); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No withdrawal requests</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="cfg-dashboard-section">
        <h2>Transaction History</h2>
        <div class="cfg-table-responsive">
            <table class="cfg-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo esc_html(date('M d, Y H:i', strtotime($trans->timestamp))); ?></td>
                                <td><span class="cfg-type cfg-type-<?php echo esc_attr($trans->type); ?>"><?php echo esc_html(ucfirst($trans->type)); ?></span></td>
                                <td class="cfg-amount-<?php echo esc_attr($trans->type); ?>">₹<?php echo number_format($trans->amount, 2); ?></td>
                                <td><?php echo esc_html($trans->reason); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No transactions yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Booking History -->
    <div class="cfg-dashboard-section">
        <h2>Booking History</h2>
        <div class="cfg-table-responsive">
            <table class="cfg-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Card</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Result</th>
                        <th>Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo esc_html(date('M d, Y H:i', strtotime($booking->created_at))); ?></td>
                                <td><?php echo esc_html($booking->card_name); ?></td>
                                <td><?php echo esc_html($booking->quantity); ?></td>
                                <td>₹<?php echo number_format($booking->cost, 2); ?></td>
                                <td>
                                    <?php if ($booking->round_status === 'completed'): ?>
                                        <?php if ($booking->is_winner): ?>
                                            <span class="cfg-result cfg-result-win">WIN</span>
                                        <?php else: ?>
                                            <span class="cfg-result cfg-result-loss">LOSS</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="cfg-result cfg-result-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $booking->payout > 0 ? '₹' . number_format($booking->payout, 2) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No bookings yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

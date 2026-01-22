<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cfg-wallet-page">
    <h2>My Wallet</h2>

    <div class="cfg-wallet-balance-card">
        <h3>Current Balance</h3>
        <div class="cfg-balance-amount">₹<?php echo number_format($balance, 2); ?></div>
    </div>

    <div class="cfg-wallet-actions">
        <a href="#" class="cfg-btn cfg-btn-primary">Recharge Wallet</a>
        <a href="<?php echo esc_url(home_url('/my-account/dashboard/')); ?>" class="cfg-btn cfg-btn-secondary">Request Withdrawal</a>
    </div>

    <div class="cfg-transaction-history">
        <h3>Transaction History</h3>
        <table class="cfg-transactions-table">
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
                            <td><span class="cfg-type-badge cfg-type-<?php echo esc_attr($trans->type); ?>"><?php echo esc_html(ucfirst($trans->type)); ?></span></td>
                            <td class="cfg-amount cfg-amount-<?php echo esc_attr($trans->type); ?>">₹<?php echo number_format($trans->amount, 2); ?></td>
                            <td><?php echo esc_html($trans->reason); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No transactions found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
